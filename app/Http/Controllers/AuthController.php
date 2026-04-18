<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\RegistrationVerification;
use App\Support\AccountDirectory;
use App\Support\PersonName;
use App\Services\RegistrationOtpDeliveryService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

use App\Models\PasswordResetToken;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    private const PENDING_REGISTRATION_EMAIL_KEY = 'pending_registration_email';

    private const GOOGLE_AUTH_INTENT_SESSION_KEY = 'google_auth_intent';

    private const GOOGLE_AUTH_INTENT_LOGIN = 'login';

    private const GOOGLE_AUTH_INTENT_REGISTER = 'register';

    private const MAX_REGISTER_VERIFY_ATTEMPTS = 5;

    private const REGISTER_OTP_EXPIRES_MINUTES = 2;

    private const REGISTER_OTP_RESEND_COOLDOWN_SECONDS = 120;

    private const MAX_RESET_VERIFY_ATTEMPTS = 5;

    private const PENDING_RESET_EMAIL_KEY = 'pending_reset_email_key';

    private const VERIFIED_RESET_EMAIL_KEY = 'verified_reset_email_key';

    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    public function __construct(
        private readonly RegistrationOtpDeliveryService $otpDeliveryService,
        private readonly PasswordResetService $resetService
    ) {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->loginThrottleKey($request);
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = max(1, (int) ceil(RateLimiter::availableIn($throttleKey)));

            return back()->withErrors([
                'email' => "Too many sign-in attempts. Please try again in {$seconds} second(s).",
            ])->onlyInput('email');
        }

        $account = AccountDirectory::findByEmail($credentials['email']);

        if (!$account || !Hash::check($credentials['password'], $account->password)) {
            RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

            return back()->withErrors([
                'email' => 'Invalid credentials.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $this->loginAccount($request, $account, $request->boolean('remember'));

        return $this->redirectAfterAuthentication($request);
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', [
            'pendingVerification' => $this->currentRegistrationVerification($request),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (AccountDirectory::emailExists((string) $value)) {
                        $fail('This email is already registered. Please sign in instead.');
                    }
                },
            ],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address (example: name@gmail.com).',
            'phone.required' => 'Please enter your phone number.',
            'phone.regex' => 'Enter a valid phone number using digits and + ( ) - symbols.',
            'password.required' => 'Please create a password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters with uppercase and numbers.',
        ]);

        $validated['name'] = PersonName::combine($validated['first_name'], $validated['last_name']);

        try {
            $verification = $this->issueRegistrationCode($validated);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'We could not send a confirmation code right now. Please try again.']);
        }

        $request->session()->put(self::PENDING_REGISTRATION_EMAIL_KEY, $verification->email);

        return redirect()
            ->route('register.verify')
            ->with('status', 'A 6-digit confirmation code was sent to your email.');
    }

    public function showRegisterVerification(Request $request)
    {
        $verification = $this->currentRegistrationVerification($request);
        if (!$verification) {
            return redirect()
                ->route('register')
                ->withErrors(['email' => 'Start registration first to receive a confirmation code.']);
        }

        return view('auth.verify-code', [
            'verification' => $verification,
        ]);
    }

    public function verifyRegisterCode(Request $request)
    {
        $verification = $this->currentRegistrationVerification($request);
        if (!$verification) {
            return redirect()
                ->route('register')
                ->withErrors(['email' => 'Registration session expired. Start registration again.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Enter the 6-digit code sent to your email.',
            'code.digits' => 'Code must be exactly 6 digits.',
        ]);

        if (now()->greaterThan($verification->code_expires_at)) {
            return back()
                ->withErrors(['code' => 'Your code has expired. Click "Resend code" to get a new one.']);
        }

        if ($verification->attempts >= self::MAX_REGISTER_VERIFY_ATTEMPTS) {
            return back()
                ->withErrors(['code' => 'Too many incorrect attempts. Click "Resend code" to get a new code.']);
        }

        if (!Hash::check($validated['code'], $verification->code_hash)) {
            $nextAttemptCount = $verification->attempts + 1;
            $verification->forceFill(['attempts' => $nextAttemptCount])->save();

            $remainingAttempts = self::MAX_REGISTER_VERIFY_ATTEMPTS - $nextAttemptCount;
            $message = $remainingAttempts > 0
                ? 'Incorrect code. You have '.$remainingAttempts.' attempt(s) remaining.'
                : 'Too many incorrect attempts. Click "Resend code" to get a new code.';

            return back()
                ->withErrors(['code' => $message]);
        }

        if (AccountDirectory::emailExists($verification->email)) {
            $verification->delete();
            $request->session()->forget(self::PENDING_REGISTRATION_EMAIL_KEY);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This email is already registered. Please sign in.']);
        }

        try {
            $rawPassword = Crypt::decryptString($verification->password_encrypted);
        } catch (DecryptException $exception) {
            report($exception);

            $verification->delete();
            $request->session()->forget(self::PENDING_REGISTRATION_EMAIL_KEY);

            return redirect()
                ->route('register')
                ->withErrors(['email' => 'Registration data expired. Please register again.']);
        }

        $user = Customer::create([
            'name' => $verification->name,
            'email' => $verification->email,
            'phone' => $verification->phone ?: null,
            'password' => Hash::make($rawPassword),
            'google_id' => $verification->google_id,
            'country' => 'Philippines',
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $verification->delete();
        $request->session()->forget(self::PENDING_REGISTRATION_EMAIL_KEY);

        $this->loginAccount($request, $user);

        return $this->redirectAfterAuthentication($request);
    }

    public function resendRegisterCode(Request $request)
    {
        $verification = $this->currentRegistrationVerification($request);
        if (!$verification) {
            return redirect()
                ->route('register')
                ->withErrors(['email' => 'Registration session expired. Start registration again.']);
        }

        if (
            $verification->last_sent_at
            && $verification->last_sent_at->diffInSeconds(now()) < self::REGISTER_OTP_RESEND_COOLDOWN_SECONDS
        ) {
            $waitSeconds = (int) ceil(
                self::REGISTER_OTP_RESEND_COOLDOWN_SECONDS - $verification->last_sent_at->diffInSeconds(now())
            );
            $waitSeconds = max(1, $waitSeconds);

            return back()
                ->withErrors(['code' => 'Please wait '.$waitSeconds.' seconds before requesting a new code.']);
        }

        try {
            $this->refreshAndSendRegistrationCode($verification);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['code' => 'We could not resend the code right now. Please try again.']);
        }

        return back()
            ->with('status', 'A new confirmation code was sent to your email.');
    }

    public function redirectToGoogleForLogin(Request $request)
    {
        return $this->redirectToGoogle($request, self::GOOGLE_AUTH_INTENT_LOGIN);
    }

    public function redirectToGoogleForRegister(Request $request)
    {
        return $this->redirectToGoogle($request, self::GOOGLE_AUTH_INTENT_REGISTER);
    }

    private function redirectToGoogle(Request $request, string $intent)
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Google login is not configured yet. Please set Google OAuth credentials.']);
        }

        $request->session()->put(self::GOOGLE_AUTH_INTENT_SESSION_KEY, $intent);

        return Socialite::driver('google')
            ->redirectUrl(route('auth.google.callback'))
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        $intent = $request->session()->pull(self::GOOGLE_AUTH_INTENT_SESSION_KEY, self::GOOGLE_AUTH_INTENT_LOGIN);
        if (!in_array($intent, [self::GOOGLE_AUTH_INTENT_LOGIN, self::GOOGLE_AUTH_INTENT_REGISTER], true)) {
            $intent = self::GOOGLE_AUTH_INTENT_LOGIN;
        }

        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('auth.google.callback'))
                ->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $googleId = (string) $googleUser->getId();
        $email = $googleUser->getEmail();

        if (empty($email)) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your Google account must have a public email address.']);
        }

        $existingAccount = AccountDirectory::findByEmail($email);
        $user = AccountDirectory::findByGoogleId($googleId);

        if (!$user) {
            $user = AccountDirectory::findCustomerByEmail($email);
        }

        if (!$user && $existingAccount && !$existingAccount->isCustomer()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Google sign-in is only available for customer accounts.']);
        }

        if ($user && empty($user->google_id)) {
            $user->google_id = $googleId;
            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
            }
            $user->save();
        }

        if ($intent === self::GOOGLE_AUTH_INTENT_REGISTER) {
            if ($user) {
                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'This Google account is already registered. Please sign in.']);
            }

            try {
                $verification = $this->issueRegistrationCode([
                    'name' => $googleUser->getName() ?: Str::before($email, '@'),
                    'email' => $email,
                    'phone' => '',
                    'password' => Str::random(40),
                    'google_id' => $googleId,
                ]);
            } catch (Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('register')
                    ->withErrors(['email' => 'We could not send a confirmation code right now. Please try again.']);
            }

            $request->session()->put(self::PENDING_REGISTRATION_EMAIL_KEY, $verification->email);

            return redirect()
                ->route('register.verify')
                ->with('status', 'A 6-digit confirmation code was sent to your email.');
        }

        if (!$user) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This Google account is not registered yet. Use Create account first.']);
        }

        $this->loginAccount($request, $user, true);

        return $this->redirectAfterAuthentication($request);
    }

    public function logout(Request $request)
    {
        foreach (array_keys(AccountDirectory::guardModelMap()) as $guard) {
            Auth::guard($guard)->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetCode(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $validated['email'];

        if (!AccountDirectory::emailExists($email)) {
            return back()->withErrors(['email' => 'No account found with this email.']);
        }

        if (RateLimiter::tooManyAttempts('reset-email:'.$email, 5)) {
            $seconds = (int) ceil(RateLimiter::availableIn('reset-email:'.$email));
            return back()->withErrors(['email' => "Too many requests. Please try again in {$seconds}s."]);
        }

        RateLimiter::hit('reset-email:'.$email, 60);

        $token = PasswordResetToken::firstOrCreate(
            ['email' => $email],
            [
                'token' => Str::random(64),
                'otp_channel' => PasswordResetToken::OTP_CHANNEL_EMAIL,
            ]
        );

        if (!$token->canResend()) {
            $seconds = (int) ceil(60 - $token->last_sent_at->diffInSeconds(now()));
            $seconds = max(1, $seconds);
            return back()->withInput()->withErrors(['email' => "Please wait {$seconds}s before resending."]);
        }

        try {
            $code = (string) random_int(100000, 999999);
            $this->resetService->send($token, $code);

            $request->session()->forget(self::VERIFIED_RESET_EMAIL_KEY);
            $request->session()->put(self::PENDING_RESET_EMAIL_KEY, $email);

            return redirect()
                ->route('password.reset', ['email' => $email])
                ->with('status', 'Reset code sent to your email. Enter the 6-digit code below.');
        } catch (Throwable $e) {
            report($e);
            return back()->withInput()->withErrors(['email' => 'Failed to send code. Try again.']);
        }
    }

    public function showResetPassword(Request $request, string $email)
    {
        $token = PasswordResetToken::findValidByEmail($email);
        if (!$token) {
            abort(404, 'Invalid or expired reset request.');
        }

        $request->session()->put(self::PENDING_RESET_EMAIL_KEY, $email);

        return view('auth.reset-password', ['email' => $email]);
    }

    public function verifyResetCode(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = $validated['email'];
        $token = PasswordResetToken::where('email', $email)->first();

        if (!$token || $token->isExpired()) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['code' => 'Invalid or expired code.']);
        }

        if ($token->attemptsExceeded(self::MAX_RESET_VERIFY_ATTEMPTS)) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['code' => 'Too many attempts. Request new code.']);
        }

        if (!Hash::check($validated['code'], $token->code_hash)) {
            $token->incrementAttempts();
            $remainingAttempts = max(0, self::MAX_RESET_VERIFY_ATTEMPTS - $token->attempts);

            return back()
                ->withInput(['email' => $email])
                ->withErrors(['code' => 'Incorrect code. Attempts remaining: '.$remainingAttempts]);
        }

        $request->session()->put(self::PENDING_RESET_EMAIL_KEY, $email);
        $request->session()->put(self::VERIFIED_RESET_EMAIL_KEY, $email);

        return redirect()
            ->route('password.reset.new', ['email' => $email])
            ->with('status', 'Code verified. Create your new password.');
    }

    public function showNewPasswordForm(Request $request, string $email)
    {
        $token = PasswordResetToken::findValidByEmail($email);
        if (!$token) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Invalid or expired reset request. Please request a new code.']);
        }

        if ($request->session()->get(self::VERIFIED_RESET_EMAIL_KEY) !== $email) {
            return redirect()
                ->route('password.reset', ['email' => $email])
                ->withErrors(['code' => 'Verify the 6-digit code first.']);
        }

        return view('auth.reset-password-new', ['email' => $email]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $email = $validated['email'];

        if ($request->session()->get(self::VERIFIED_RESET_EMAIL_KEY) !== $email) {
            return redirect()
                ->route('password.reset', ['email' => $email])
                ->withErrors(['code' => 'Verify the 6-digit code first.']);
        }

        $token = PasswordResetToken::where('email', $email)->first();

        if (!$token || $token->isExpired()) {
            return redirect()
                ->route('password.reset', ['email' => $email])
                ->withErrors(['code' => 'Invalid or expired code.']);
        }

        $user = AccountDirectory::findByEmail($email);

        if (!$user) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'No account found with this email.']);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        $token->delete();
        $request->session()->forget([
            self::PENDING_RESET_EMAIL_KEY,
            self::VERIFIED_RESET_EMAIL_KEY,
        ]);

        return redirect()->route('login')->with('status', 'Password reset successfully. Sign in with new password.');
    }

    private function currentPasswordResetToken(Request $request): ?PasswordResetToken
    {
        $email = $request->session()->get(self::PENDING_RESET_EMAIL_KEY);
        if (!$email) {
            return null;
        }

        return PasswordResetToken::where('email', $email)->first();
    }

    private function loginThrottleKey(Request $request): string
    {
        $email = strtolower(trim((string) $request->input('email')));

        return 'login:'.$email.'|'.$request->ip();
    }

    private function defaultRedirectRoute(): string
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return route('admin.dashboard');
        }

        if ($user?->isStaff()) {
            return route('staff.dashboard');
        }

        return route('home');
    }

    private function redirectAfterAuthentication(Request $request)
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            $request->session()->forget('url.intended');

            return redirect()->route('admin.dashboard');
        }

        if ($user?->isStaff()) {
            $request->session()->forget('url.intended');

            return redirect()->route('staff.dashboard');
        }

        return redirect()->intended($this->defaultRedirectRoute());
    }

    private function currentRegistrationVerification(Request $request): ?RegistrationVerification
    {
        $email = $request->session()->get(self::PENDING_REGISTRATION_EMAIL_KEY);
        if (!$email) {
            return null;
        }

        return RegistrationVerification::query()->where('email', $email)->first();
    }

    private function issueRegistrationCode(array $validated): RegistrationVerification
    {
        $code = (string) random_int(100000, 999999);

        $verification = RegistrationVerification::query()->updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'google_id' => $validated['google_id'] ?? null,
                'phone' => $validated['phone'],
                'otp_channel' => RegistrationVerification::OTP_CHANNEL_EMAIL,
                'password_encrypted' => Crypt::encryptString($validated['password']),
                'code_hash' => Hash::make($code),
                'code_expires_at' => now()->addMinutes(self::REGISTER_OTP_EXPIRES_MINUTES),
                'attempts' => 0,
                'last_sent_at' => now(),
            ]
        );

        $this->otpDeliveryService->send($verification, $code);

        return $verification;
    }

    private function refreshAndSendRegistrationCode(RegistrationVerification $verification): void
    {
        $code = (string) random_int(100000, 999999);

        $verification->forceFill([
            'otp_channel' => RegistrationVerification::OTP_CHANNEL_EMAIL,
            'code_hash' => Hash::make($code),
            'code_expires_at' => now()->addMinutes(self::REGISTER_OTP_EXPIRES_MINUTES),
            'attempts' => 0,
            'last_sent_at' => now(),
        ])->save();

        $this->otpDeliveryService->send($verification, $code);
    }

    private function loginAccount(Request $request, Account $account, bool $remember = false): void
    {
        $guard = AccountDirectory::guardFor($account);

        Auth::guard($guard)->login($account, $remember);
        Auth::shouldUse($guard);
        $request->session()->regenerate();
    }
}

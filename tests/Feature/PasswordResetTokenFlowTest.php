<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCodeMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTokenFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_can_request_password_reset_code(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.reset', ['email' => $user->email]));
        $response->assertSessionHas('status', 'Reset code sent to your email. Enter the 6-digit code below.');

        $token = PasswordResetToken::query()->where('email', $user->email)->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->code_hash);
        $this->assertNotNull($token->code_expires_at);
        $this->assertSame(0, $token->attempts);
        $this->assertNotNull($token->last_sent_at);
        $this->assertSame(PasswordResetToken::OTP_CHANNEL_EMAIL, $token->otp_channel);

        Mail::assertQueued(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });
    }

    public function test_valid_otp_redirects_to_separate_new_password_page(): void
    {
        $user = User::factory()->create([
            'email' => 'otpstep@example.com',
        ]);

        PasswordResetToken::query()->create([
            'email' => $user->email,
            'token' => 'legacy-token',
            'code_hash' => Hash::make('123456'),
            'code_expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now()->subSeconds(61),
            'otp_channel' => PasswordResetToken::OTP_CHANNEL_EMAIL,
        ]);

        $response = $this->post(route('password.verify'), [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertRedirect(route('password.reset.new', ['email' => $user->email]));
        $response->assertSessionHas('status', 'Code verified. Create your new password.');

        $this->get(route('password.reset.new', ['email' => $user->email]))
            ->assertOk()
            ->assertSee('Create New Password');
    }

    public function test_password_update_requires_verified_otp_step(): void
    {
        $user = User::factory()->create([
            'email' => 'mustverify@example.com',
            'password' => Hash::make('OldPass123'),
        ]);

        PasswordResetToken::query()->create([
            'email' => $user->email,
            'token' => 'legacy-token',
            'code_hash' => Hash::make('123456'),
            'code_expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now()->subSeconds(61),
            'otp_channel' => PasswordResetToken::OTP_CHANNEL_EMAIL,
        ]);

        $response = $this->post(route('password.update'), [
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertRedirect(route('password.reset', ['email' => $user->email]));
        $response->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertTrue(Hash::check('OldPass123', $user->password));
    }

    public function test_user_can_reset_password_after_verifying_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'fullflow@example.com',
            'password' => Hash::make('OldPass123'),
        ]);

        PasswordResetToken::query()->create([
            'email' => $user->email,
            'token' => 'legacy-token',
            'code_hash' => Hash::make('123456'),
            'code_expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now()->subSeconds(61),
            'otp_channel' => PasswordResetToken::OTP_CHANNEL_EMAIL,
        ]);

        $this->post(route('password.verify'), [
            'email' => $user->email,
            'code' => '123456',
        ])->assertRedirect(route('password.reset.new', ['email' => $user->email]));

        $response = $this->post(route('password.update'), [
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Password reset successfully. Sign in with new password.');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass123', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }
}

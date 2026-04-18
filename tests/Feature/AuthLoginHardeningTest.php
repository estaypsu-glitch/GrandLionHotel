<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthLoginHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_repeated_failed_attempts(): void
    {
        $email = 'security.login@example.com';
        $throttleKey = $this->throttleKey($email);
        RateLimiter::clear($throttleKey);

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('SecurePass123'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->post(route('login.perform'), [
                'email' => $email,
                'password' => 'WrongPass123',
            ]);

            $response->assertSessionHasErrors('email');
        }

        $limitedResponse = $this->post(route('login.perform'), [
            'email' => $email,
            'password' => 'WrongPass123',
        ]);

        $limitedResponse->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many sign-in attempts.',
            (string) session('errors')->first('email')
        );

        RateLimiter::clear($throttleKey);
    }

    public function test_successful_login_clears_throttle_counter(): void
    {
        $email = 'clear.counter@example.com';
        $throttleKey = $this->throttleKey($email);
        RateLimiter::clear($throttleKey);

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('RightPass123'),
        ]);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->post(route('login.perform'), [
                'email' => $email,
                'password' => 'WrongPass123',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.perform'), [
            'email' => $email,
            'password' => 'RightPass123',
        ])->assertRedirect(route('home'));

        $this->post(route('logout'))->assertRedirect(route('home'));

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $response = $this->post(route('login.perform'), [
                'email' => $email,
                'password' => 'WrongPass123',
            ]);

            $response->assertSessionHasErrors('email');
            $this->assertStringNotContainsString(
                'Too many sign-in attempts.',
                (string) session('errors')->first('email')
            );
        }

        RateLimiter::clear($throttleKey);
    }

    public function test_staff_login_redirects_to_staff_dashboard_even_with_frontend_intended_url(): void
    {
        $email = 'staff.login@example.com';

        User::factory()->staff()->create([
            'email' => $email,
            'password' => Hash::make('StaffPass123'),
        ]);

        $this->withSession([
            'url.intended' => route('home'),
        ])->post(route('login.perform'), [
            'email' => $email,
            'password' => 'StaffPass123',
        ])->assertRedirect(route('staff.dashboard'));
    }

    public function test_admin_login_redirects_to_admin_dashboard_even_with_frontend_intended_url(): void
    {
        $email = 'admin.login@example.com';

        User::factory()->admin()->create([
            'email' => $email,
            'password' => Hash::make('AdminPass123'),
        ]);

        $this->withSession([
            'url.intended' => route('home'),
        ])->post(route('login.perform'), [
            'email' => $email,
            'password' => 'AdminPass123',
        ])->assertRedirect(route('admin.dashboard'));
    }

    private function throttleKey(string $email): string
    {
        return 'login:'.strtolower(trim($email)).'|127.0.0.1';
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_security_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.security'));

        $response->assertOk();
        $response->assertSee('Account Security');
        $response->assertSee('Change Password');
    }

    public function test_user_can_update_password_from_security_page(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)->from(route('profile.security'))->patch(route('profile.password.update'), [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'logout_other_devices' => '1',
        ]);

        $response->assertRedirect(route('profile.security'));
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
        $this->assertNotNull($user->password_changed_at);
    }

    public function test_password_update_rejects_weak_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)->from(route('profile.security'))->patch(route('profile.password.update'), [
            'current_password' => 'OldPassword123!',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);

        $response->assertRedirect(route('profile.security'));
        $response->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
    }
}

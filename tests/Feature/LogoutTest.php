<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout_via_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout_via_get(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }
}

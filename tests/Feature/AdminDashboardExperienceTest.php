<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_operational_sections_and_recent_bookings(): void
    {
        $admin = User::factory()->admin()->create();

        $room = Room::factory()->create([
            'is_available' => true,
        ]);

        $booking = Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Open Booking Desk');
        $response->assertSee('Priority Queues');
        $response->assertSee('Quick Actions');
        $response->assertSee('Latest Booking Activity');
        $response->assertSee('#'.$booking->id);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_navigation_is_limited_to_operational_pages(): void
    {
        $staff = Staff::factory()->create();

        $dashboardResponse = $this->actingAs($staff, 'staff')->get(route('staff.dashboard'));
        $bookingsResponse = $this->actingAs($staff, 'staff')->get(route('staff.bookings.index'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee(route('staff.dashboard'), false);
        $dashboardResponse->assertSee(route('staff.arrivals'), false);
        $dashboardResponse->assertSee(route('staff.bookings.index'), false);
        $dashboardResponse->assertDontSee(route('staff.bookings.create'), false);
        $dashboardResponse->assertDontSee('/staff/rooms', false);

        $bookingsResponse->assertOk();
        $bookingsResponse->assertSee(route('staff.bookings.create'), false);
    }

    public function test_staff_room_management_routes_are_not_available(): void
    {
        $staff = Staff::factory()->create();
        $room = Room::factory()->create();

        $this->actingAs($staff, 'staff')
            ->get('/staff/rooms')
            ->assertNotFound();

        $this->actingAs($staff, 'staff')
            ->patch('/staff/rooms/'.$room->getKey().'/cleaning-status', [
                'room_status_id' => 1,
            ])
            ->assertNotFound();
    }
}

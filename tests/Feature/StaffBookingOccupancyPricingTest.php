<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomStatus;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffBookingOccupancyPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['pricing.extra_bedding_fee_per_night' => 500]);

        DB::table('room_status')->insert([
            'name' => 'Clean',
            'slug' => 'clean',
            'description' => 'Ready for booking',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_staff_recalculates_unpaid_booking_amount_when_occupancy_changes(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        $booking = $this->createBooking($customer, $room, $staff);

        $booking->payment()->create([
            'amount' => 4000,
            'method' => 'pending',
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(route('staff.bookings.occupancy', $booking), [
            'adults' => 3,
            'kids' => 0,
            'extra_bedding_confirmed' => 1,
        ]);

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh()->load('payment', 'guestDetail');

        $this->assertSame(3, $booking->guests);
        $this->assertSame('5000.00', number_format((float) $booking->payment->amount, 2, '.', ''));
        $this->assertSame(3, (int) $booking->guestDetail->adults);
    }

    public function test_staff_blocks_paid_booking_occupancy_changes_that_would_change_total(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        $booking = $this->createBooking($customer, $room, $staff);

        $booking->payment()->create([
            'amount' => 4000,
            'method' => Payment::METHOD_CASH,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($staff, 'staff')
            ->from(route('staff.bookings.show', $booking))
            ->patch(route('staff.bookings.occupancy', $booking), [
                'adults' => 3,
                'kids' => 0,
                'extra_bedding_confirmed' => 1,
            ]);

        $response->assertRedirect(route('staff.bookings.show', $booking));
        $response->assertSessionHasErrors('occupancy');

        $booking->refresh()->load('payment', 'guestDetail');

        $this->assertSame(2, $booking->guests);
        $this->assertSame('4000.00', number_format((float) $booking->payment->amount, 2, '.', ''));
        $this->assertSame(2, (int) $booking->guestDetail->adults);
    }

    private function createRoom(array $attributes = []): Room
    {
        $cleanStatusId = (int) RoomStatus::query()->where('slug', 'clean')->value('room_status_id');

        return Room::factory()->create(array_merge([
            'room_status_id' => $cleanStatusId,
        ], $attributes));
    }

    private function createBooking(Customer $customer, Room $room, Staff $staff): Booking
    {
        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'status' => 'confirmed',
            'notes' => 'Occupancy pricing test booking',
            'staff_id' => $staff->id,
        ]);

        $booking->guestDetail()->create([
            'first_name' => 'Occupancy',
            'last_name' => 'Guest',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'adults' => 2,
            'kids' => 0,
        ]);

        return $booking;
    }
}

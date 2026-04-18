<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomStatus;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingRoomTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('room_status')->insert([
            [
                'name' => 'Clean',
                'slug' => 'clean',
                'description' => 'Ready for booking',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_staff_can_transfer_unpaid_booking_and_recalculate_amount(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $currentRoom = $this->createRoom([
            'name' => 'Room 101',
            'price_per_night' => 2000,
            'capacity' => 2,
        ]);
        $newRoom = $this->createRoom([
            'name' => 'Room 202',
            'price_per_night' => 3000,
            'capacity' => 3,
        ]);

        $booking = $this->createTransferableBooking($customer, $currentRoom, $staff, [
            'status' => 'confirmed',
        ]);

        $booking->payment()->create([
            'amount' => 4000,
            'method' => 'pending',
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(
            route('staff.bookings.transfer-room', $booking),
            [
                'room_id' => $newRoom->id,
            ]
        );

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $booking->load('payment');

        $this->assertSame($newRoom->id, $booking->room_id);
        $this->assertSame($staff->id, $booking->staff_id);
        $this->assertSame('6000.00', number_format((float) $booking->payment->amount, 2, '.', ''));
    }

    public function test_staff_cannot_transfer_paid_booking_to_room_with_different_total(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $currentRoom = $this->createRoom([
            'name' => 'Room 101',
            'price_per_night' => 2500,
            'capacity' => 2,
        ]);
        $higherRoom = $this->createRoom([
            'name' => 'Room 303',
            'price_per_night' => 3500,
            'capacity' => 2,
        ]);

        $booking = $this->createTransferableBooking($customer, $currentRoom, $staff, [
            'status' => 'confirmed',
        ]);

        $booking->payment()->create([
            'amount' => 5000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($staff, 'staff')->from(route('staff.bookings.show', $booking))->patch(
            route('staff.bookings.transfer-room', $booking),
            [
                'room_id' => $higherRoom->id,
            ]
        );

        $response->assertRedirect(route('staff.bookings.show', $booking));
        $response->assertSessionHasErrors('room_id');

        $booking->refresh();
        $booking->load('payment');

        $this->assertSame($currentRoom->id, $booking->room_id);
        $this->assertSame('5000.00', number_format((float) $booking->payment->amount, 2, '.', ''));
    }

    public function test_staff_can_transfer_paid_booking_to_same_total_room(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $currentRoom = $this->createRoom([
            'name' => 'Room 101',
            'price_per_night' => 2800,
            'capacity' => 2,
        ]);
        $sameRateRoom = $this->createRoom([
            'name' => 'Room 102',
            'price_per_night' => 2800,
            'capacity' => 2,
        ]);

        $booking = $this->createTransferableBooking($customer, $currentRoom, $staff, [
            'status' => 'confirmed',
        ]);

        $booking->payment()->create([
            'amount' => 5600,
            'method' => 'gcash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(
            route('staff.bookings.transfer-room', $booking),
            [
                'room_id' => $sameRateRoom->id,
            ]
        );

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $booking->load('payment');

        $this->assertSame($sameRateRoom->id, $booking->room_id);
        $this->assertSame('5600.00', number_format((float) $booking->payment->amount, 2, '.', ''));
    }

    private function createRoom(array $attributes = []): Room
    {
        $cleanStatusId = (int) RoomStatus::query()->where('slug', 'clean')->value('room_status_id');

        return Room::factory()->create(array_merge([
            'room_status_id' => $cleanStatusId,
        ], $attributes));
    }

    private function createTransferableBooking(Customer $customer, Room $room, Staff $staff, array $attributes = []): Booking
    {
        $booking = Booking::query()->create(array_merge([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'status' => 'pending',
            'notes' => 'Room transfer test booking',
            'staff_id' => $staff->id,
        ], $attributes));

        $booking->guestDetail()->create([
            'first_name' => 'Transfer',
            'last_name' => 'Guest',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'adults' => 2,
            'kids' => 0,
        ]);

        return $booking;
    }
}

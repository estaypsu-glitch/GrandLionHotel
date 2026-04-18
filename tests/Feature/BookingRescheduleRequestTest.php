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

class BookingRescheduleRequestTest extends TestCase
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

    public function test_customer_can_request_schedule_change_for_confirmed_unpaid_booking(): void
    {
        $customer = Customer::factory()->create();
        $room = $this->createRoom();
        $booking = $this->createConfirmedUnpaidBooking($customer, $room);

        $response = $this->actingAs($customer, 'customer')->patch(
            route('bookings.request-reschedule', $booking),
            [
                'requested_check_in' => now()->addDays(5)->toDateString(),
                'requested_check_out' => now()->addDays(7)->toDateString(),
                'reschedule_request_notes' => 'Need to move the trip because of work schedule.',
            ]
        );

        $response->assertRedirect(route('bookings.show', $booking));

        $booking->refresh();
        $this->assertSame(now()->addDays(5)->toDateString(), $booking->requested_check_in?->toDateString());
        $this->assertSame(now()->addDays(7)->toDateString(), $booking->requested_check_out?->toDateString());
        $this->assertSame('Need to move the trip because of work schedule.', $booking->reschedule_request_notes);
        $this->assertNotNull($booking->reschedule_requested_at);
    }

    public function test_staff_can_apply_requested_schedule_and_update_unpaid_amount(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom(['price_per_night' => 2000]);
        $booking = $this->createConfirmedUnpaidBooking($customer, $room, [
            'staff_id' => $staff->id,
            'requested_check_in' => now()->addDays(6)->toDateString(),
            'requested_check_out' => now()->addDays(9)->toDateString(),
            'reschedule_request_notes' => 'Move to next week.',
            'reschedule_requested_at' => now(),
        ]);

        $booking->payment()->create([
            'amount' => 4000,
            'method' => 'pending',
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(
            route('staff.bookings.apply-reschedule-request', $booking)
        );

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $booking->load('payment');

        $this->assertSame(now()->addDays(6)->toDateString(), $booking->check_in->toDateString());
        $this->assertSame(now()->addDays(9)->toDateString(), $booking->check_out->toDateString());
        $this->assertNull($booking->requested_check_in);
        $this->assertNull($booking->requested_check_out);
        $this->assertNull($booking->reschedule_request_notes);
        $this->assertNull($booking->reschedule_requested_at);
        $this->assertSame($staff->id, $booking->staff_id);
        $this->assertSame('6000.00', number_format((float) $booking->payment->amount, 2, '.', ''));
    }

    public function test_staff_can_directly_reschedule_confirmed_unpaid_booking_without_customer_request(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom(['price_per_night' => 2500]);
        $booking = $this->createConfirmedUnpaidBooking($customer, $room, [
            'staff_id' => $staff->id,
        ]);

        $booking->payment()->create([
            'amount' => 5000,
            'method' => 'pending',
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(
            route('staff.bookings.reschedule', $booking),
            [
                'check_in' => now()->addDays(7)->toDateString(),
                'check_out' => now()->addDays(10)->toDateString(),
            ]
        );

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $booking->load('payment');

        $this->assertSame(now()->addDays(7)->toDateString(), $booking->check_in->toDateString());
        $this->assertSame(now()->addDays(10)->toDateString(), $booking->check_out->toDateString());
        $this->assertNull($booking->requested_check_in);
        $this->assertNull($booking->requested_check_out);
        $this->assertNull($booking->reschedule_request_notes);
        $this->assertNull($booking->reschedule_requested_at);
        $this->assertSame($staff->id, $booking->staff_id);
        $this->assertSame('7500.00', number_format((float) $booking->payment->amount, 2, '.', ''));
    }

    public function test_staff_can_apply_requested_schedule_for_confirmed_booking_with_pending_verification_payment(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom(['price_per_night' => 1800]);
        $booking = $this->createConfirmedUnpaidBooking($customer, $room, [
            'staff_id' => $staff->id,
            'requested_check_in' => now()->addDays(8)->toDateString(),
            'requested_check_out' => now()->addDays(11)->toDateString(),
            'reschedule_request_notes' => 'Please move to a later date.',
            'reschedule_requested_at' => now(),
        ]);

        $booking->payment()->create([
            'amount' => 3600,
            'method' => 'gcash',
            'status' => 'pending_verification',
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(
            route('staff.bookings.apply-reschedule-request', $booking)
        );

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $booking->load('payment');

        $this->assertSame(now()->addDays(8)->toDateString(), $booking->check_in->toDateString());
        $this->assertSame(now()->addDays(11)->toDateString(), $booking->check_out->toDateString());
        $this->assertNull($booking->requested_check_in);
        $this->assertNull($booking->requested_check_out);
        $this->assertNull($booking->reschedule_request_notes);
        $this->assertNull($booking->reschedule_requested_at);
        $this->assertSame($staff->id, $booking->staff_id);
        $this->assertSame('5400.00', number_format((float) $booking->payment->amount, 2, '.', ''));
    }

    public function test_staff_can_directly_reschedule_confirmed_booking_with_paid_payment(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom(['price_per_night' => 2300]);
        $booking = $this->createConfirmedUnpaidBooking($customer, $room, [
            'staff_id' => $staff->id,
        ]);

        $booking->payment()->create([
            'amount' => 4600,
            'method' => 'gcash',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(
            route('staff.bookings.reschedule', $booking),
            [
                'check_in' => now()->addDays(9)->toDateString(),
                'check_out' => now()->addDays(12)->toDateString(),
            ]
        );

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $booking->load('payment');

        $this->assertSame(now()->addDays(9)->toDateString(), $booking->check_in->toDateString());
        $this->assertSame(now()->addDays(12)->toDateString(), $booking->check_out->toDateString());
        $this->assertSame('4600.00', number_format((float) $booking->payment->amount, 2, '.', ''));
        $this->assertSame($staff->id, $booking->staff_id);
    }

    public function test_staff_can_directly_reschedule_confirmed_booking_even_if_check_in_date_has_started(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom(['price_per_night' => 1900]);
        $booking = $this->createConfirmedUnpaidBooking($customer, $room, [
            'staff_id' => $staff->id,
            'check_in' => now()->subDay()->toDateString(),
            'check_out' => now()->toDateString(),
        ]);

        $booking->payment()->create([
            'amount' => 1900,
            'method' => 'pending',
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(
            route('staff.bookings.reschedule', $booking),
            [
                'check_in' => now()->addDays(3)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
            ]
        );

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $this->assertSame(now()->addDays(3)->toDateString(), $booking->check_in->toDateString());
        $this->assertSame(now()->addDays(5)->toDateString(), $booking->check_out->toDateString());
        $this->assertSame($staff->id, $booking->staff_id);
    }

    private function createRoom(array $attributes = []): Room
    {
        $cleanStatusId = (int) RoomStatus::query()->where('slug', 'clean')->value('room_status_id');

        return Room::factory()->create(array_merge([
            'room_status_id' => $cleanStatusId,
        ], $attributes));
    }

    private function createConfirmedUnpaidBooking(Customer $customer, Room $room, array $attributes = []): Booking
    {
        $booking = Booking::query()->create(array_merge([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'status' => 'confirmed',
            'notes' => 'Confirmed booking for reschedule test',
        ], $attributes));

        $booking->guestDetail()->create([
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'adults' => 2,
            'kids' => 0,
        ]);

        return $booking;
    }
}

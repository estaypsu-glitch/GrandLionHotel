<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\RefundRequest;
use App\Models\Room;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RefundRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        DB::table('room_status')->insert([
            'name' => 'Clean',
            'slug' => 'clean',
            'description' => 'Ready for booking',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_customer_paid_cancellation_creates_pending_refund_request(): void
    {
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);
        $refundReason = 'Change of travel plans due to a family emergency.';

        $response = $this->actingAs($customer, 'customer')->patch(route('bookings.cancel', $booking), [
            'refund_reason' => $refundReason,
        ]);

        $response->assertRedirect(route('bookings.show', $booking));

        $payment = $booking->fresh()->payment()->firstOrFail();

        $this->assertDatabaseHas('refund_requests', [
            'booking_id' => $booking->booking_id,
            'payment_id' => $payment->payment_id,
            'customer_id' => $customer->customer_id,
            'reason' => $refundReason,
            'status' => 'pending',
        ]);

        $refundRequest = RefundRequest::query()->where('payment_id', $payment->payment_id)->firstOrFail();
        $this->assertStringContainsString('original payment method: Cash', (string) $refundRequest->notes);
    }

    public function test_customer_paid_cancellation_requires_refund_reason(): void
    {
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);

        $response = $this->actingAs($customer, 'customer')
            ->from(route('bookings.show', $booking))
            ->patch(route('bookings.cancel', $booking));

        $response->assertRedirect(route('bookings.show', $booking));
        $response->assertSessionHasErrors('refund_reason');

        $booking->refresh();

        $this->assertSame('confirmed', $booking->status);
        $this->assertDatabaseCount('refund_requests', 0);
    }

    public function test_admin_paid_cancellation_creates_pending_refund_request(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);

        $response = $this->actingAs($admin, 'admin')->patch(
            route('admin.bookings.update-status', $booking),
            ['status' => 'cancelled']
        );

        $response->assertRedirect(route('admin.bookings.show', $booking));

        $payment = $booking->fresh()->payment()->firstOrFail();

        $this->assertDatabaseHas('refund_requests', [
            'booking_id' => $booking->booking_id,
            'payment_id' => $payment->payment_id,
            'customer_id' => $customer->customer_id,
            'status' => 'pending',
        ]);

        $refundRequest = RefundRequest::query()->where('payment_id', $payment->payment_id)->firstOrFail();
        $this->assertStringContainsString('original payment method: Cash', (string) $refundRequest->notes);
    }

    public function test_staff_paid_cancellation_creates_pending_refund_request(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);

        $response = $this->actingAs($staff, 'staff')->patch(route('staff.bookings.cancel', $booking));

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $payment = $booking->fresh()->payment()->firstOrFail();

        $this->assertDatabaseHas('refund_requests', [
            'booking_id' => $booking->booking_id,
            'payment_id' => $payment->payment_id,
            'customer_id' => $customer->customer_id,
            'status' => 'pending',
        ]);

        $refundRequest = RefundRequest::query()->where('payment_id', $payment->payment_id)->firstOrFail();
        $this->assertStringContainsString('original payment method: Cash', (string) $refundRequest->notes);
    }

    private function createPaidBooking(Customer $customer): Booking
    {
        $room = Room::factory()->create();

        $booking = Booking::factory()->create([
            'customer_id' => $customer->customer_id,
            'room_id' => $room->room_id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'actual_check_in_at' => null,
            'actual_check_out_at' => null,
        ]);

        $booking->payment()->update([
            'amount' => (float) $booking->total_price,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $booking->fresh(['payment']);
    }
}

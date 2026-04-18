<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrPaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_pay_with_qr_wallet_methods(): void
    {
        $user = User::factory()->create();

        foreach (['gcash', 'paymaya'] as $method) {
            $room = Room::factory()->create(['is_available' => true]);
            $booking = Booking::factory()->create([
                'user_id' => $user->id,
                'room_id' => $room->id,
                'status' => 'confirmed',
                'payment_status' => 'unpaid',
            ]);

            $qrReference = 'TEST-'.strtoupper($method).'-123456';

            $response = $this->actingAs($user)->post(route('payments.process', $booking), [
                'method' => $method,
                'qr_reference' => $qrReference,
            ]);

            $response->assertRedirect(route('bookings.success', $booking));

            $booking->refresh()->load('payment');
            $this->assertSame('paid', $booking->payment_status);
            $this->assertNotNull($booking->payment);
            $this->assertSame($method, $booking->payment->method);
            $this->assertSame('online_qr', data_get($booking->payment->meta, 'source'));
            $this->assertSame($qrReference, data_get($booking->payment->meta, 'qr_reference'));
        }
    }

    public function test_qr_wallet_payment_requires_qr_reference(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['is_available' => true]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->post(route('payments.process', $booking), [
            'method' => 'gcash',
        ]);

        $response->assertSessionHasErrors('qr_reference');

        $booking->refresh();
        $this->assertSame('unpaid', $booking->payment_status);
    }

    public function test_payment_service_does_not_downgrade_completed_booking_status(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'completed',
            'payment_status' => 'unpaid',
        ]);

        app(PaymentService::class)->charge($booking, 'cash');

        $booking->refresh();
        $this->assertSame('completed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
    }

    public function test_payment_service_is_idempotent_for_the_same_booking(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        $service = app(PaymentService::class);

        $firstPayment = $service->charge($booking, 'cash');
        $secondPayment = $service->charge($booking, 'gcash', [
            'qr_reference' => 'DUPLICATE-CALL-TEST',
        ]);

        $booking->refresh();

        $this->assertSame($firstPayment->id, $secondPayment->id);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertDatabaseCount('payments', 1);
    }

}

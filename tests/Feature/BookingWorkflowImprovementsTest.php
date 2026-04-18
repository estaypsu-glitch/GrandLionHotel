<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RegistrationVerification;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingWorkflowImprovementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_booking_redirects_to_details_when_status_is_pending(): void
    {
        $customer = User::factory()->create([
            'phone' => '+639000000001',
            'address_line' => '221B Baker Street',
            'city' => 'Manila',
            'province' => 'Metro Manila (NCR)',
        ]);

        $room = Room::factory()->create([
            'is_available' => true,
            'capacity' => 3,
            'price_per_night' => 1500,
        ]);

        $response = $this->actingAs($customer)->post(route('bookings.store'), [
            'room_id' => $room->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
        ]);

        $booking = Booking::query()->firstOrFail();

        $response->assertRedirect(route('bookings.show', $booking));
        $response->assertSessionHas('status', 'Booking request submitted. Wait for staff confirmation before payment.');
        $this->assertSame('pending', $booking->status);
        $this->assertSame('unpaid', $booking->payment_status);
    }

    public function test_admin_cannot_transition_pending_booking_directly_to_completed(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create([
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'actual_check_in_at' => null,
            'actual_check_out_at' => null,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.bookings.update-status', $booking), [
            'status' => 'completed',
        ]);

        $response->assertSessionHasErrors('status');

        $booking->refresh();
        $this->assertSame('pending', $booking->status);
        $this->assertNull($booking->actual_check_out_at);
    }

    public function test_staff_walk_in_confirmation_uses_reservation_email_when_user_is_missing(): void
    {
        Mail::fake();

        $staff = User::factory()->staff()->create();
        $room = Room::factory()->create(['is_available' => true]);

        $booking = Booking::factory()->create([
            'user_id' => null,
            'room_id' => $room->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'reservation_meta' => [
                'customer_name' => 'Walk In Guest',
                'customer_email' => 'walkin@example.com',
                'customer_phone' => '+639000000009',
            ],
        ]);

        $response = $this->actingAs($staff)->patch(route('staff.bookings.confirm', $booking));

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);

        Mail::assertQueued(BookingConfirmedMail::class, function (BookingConfirmedMail $mail): bool {
            return $mail->hasTo('walkin@example.com');
        });
    }

    public function test_staff_booking_queue_displays_walk_in_guest_name_and_supports_search(): void
    {
        $staff = User::factory()->staff()->create();
        $room = Room::factory()->create(['is_available' => true]);

        Booking::factory()->create([
            'user_id' => null,
            'room_id' => $room->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'reservation_meta' => [
                'customer_name' => 'Walk In Guest',
                'customer_email' => 'walkin@example.com',
            ],
        ]);

        Booking::factory()->create([
            'user_id' => null,
            'room_id' => $room->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'reservation_meta' => [
                'customer_name' => 'Another Guest',
                'customer_email' => 'another@example.com',
            ],
        ]);

        $listResponse = $this->actingAs($staff)->get(route('staff.bookings.index'));
        $listResponse->assertOk();
        $listResponse->assertSee('Walk In Guest');

        $searchResponse = $this->actingAs($staff)->get(route('staff.bookings.index', [
            'q' => 'Walk In Guest',
        ]));

        $searchResponse->assertOk();
        $searchResponse->assertSee('Walk In Guest');
        $searchResponse->assertDontSee('Another Guest');
    }

    public function test_staff_can_download_receipt_for_paid_walk_in_booking(): void
    {
        $staff = User::factory()->staff()->create();
        $room = Room::factory()->create(['is_available' => true]);

        $booking = Booking::factory()->create([
            'user_id' => null,
            'room_id' => $room->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'staff_id' => $staff->id,
            'reservation_meta' => [
                'customer_name' => 'Walk In Guest',
                'customer_email' => 'walkin@example.com',
                'customer_phone' => '+639000000321',
            ],
        ]);

        Payment::query()->create([
            'booking_id' => $booking->id,
            'amount' => (float) $booking->total_price,
            'method' => 'cash',
            'status' => 'paid',
            'transaction_reference' => 'WALKIN-REF-1001',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($staff)->get(route('staff.bookings.receipt', $booking));

        $response->assertOk();
        $this->assertStringContainsString(
            'booking-receipt-'.$booking->id.'.pdf',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_staff_walk_in_booking_allows_guest_count_above_ten_when_room_capacity_allows(): void
    {
        $staff = User::factory()->staff()->create();
        $room = Room::factory()->create([
            'is_available' => true,
            'capacity' => 12,
            'price_per_night' => 2500,
        ]);

        $response = $this->actingAs($staff)->post(route('staff.bookings.store'), [
            'customer_name' => 'Large Group Guest',
            'customer_email' => 'large.group@example.com',
            'customer_phone' => '+639171234567',
            'room_id' => $room->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guests' => 12,
            'payment_preference' => 'cash',
        ]);

        $booking = Booking::query()->first();

        $this->assertNotNull($booking);
        $response->assertRedirect(route('staff.bookings.show', $booking));
        $this->assertSame(12, $booking->guests);
        $this->assertSame('pending', $booking->status);
    }

    public function test_staff_walk_in_booking_rejects_invalid_phone_format(): void
    {
        $staff = User::factory()->staff()->create();
        $room = Room::factory()->create([
            'is_available' => true,
            'capacity' => 4,
        ]);

        $response = $this->actingAs($staff)->from(route('staff.bookings.create'))->post(route('staff.bookings.store'), [
            'customer_name' => 'Phone Validation Guest',
            'customer_email' => 'phone.validation@example.com',
            'customer_phone' => 'invalid-phone-format',
            'room_id' => $room->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guests' => 2,
        ]);

        $response->assertRedirect(route('staff.bookings.create'));
        $response->assertSessionHasErrors('customer_phone');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_registration_verification_logs_in_new_user_after_successful_code(): void
    {
        $verification = RegistrationVerification::query()->create([
            'name' => 'Auto Login User',
            'email' => 'autologin@example.com',
            'phone' => '+639000000011',
            'otp_channel' => RegistrationVerification::OTP_CHANNEL_EMAIL,
            'password_encrypted' => Crypt::encryptString('password1234'),
            'code_hash' => Hash::make('123456'),
            'code_expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        $response = $this
            ->withSession(['pending_registration_email' => $verification->email])
            ->post(route('register.verify.perform'), ['code' => '123456']);

        $user = User::query()->where('email', $verification->email)->first();

        $this->assertNotNull($user);
        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('registration_verifications', ['email' => $verification->email]);
    }

    public function test_customer_booking_rejects_invalid_state_province_value(): void
    {
        $customer = User::factory()->create([
            'phone' => '+639000000001',
            'address_line' => '221B Baker Street',
            'city' => 'Manila',
            'province' => 'Metro Manila (NCR)',
        ]);

        $room = Room::factory()->create([
            'is_available' => true,
            'capacity' => 3,
            'price_per_night' => 1500,
        ]);

        $response = $this->actingAs($customer)->post(route('bookings.store'), [
            'room_id' => $room->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guests' => 2,
            'state_province' => 'NotARealProvince',
        ]);

        $response->assertSessionHasErrors('state_province');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_customer_nightly_booking_uses_hotel_standard_check_in_and_check_out_times(): void
    {
        $customer = User::factory()->create([
            'phone' => '+639000000001',
            'address_line' => '221B Baker Street',
            'city' => 'Manila',
            'province' => 'Metro Manila (NCR)',
        ]);

        $room = Room::factory()->create([
            'is_available' => true,
            'capacity' => 3,
            'price_per_night' => 1500,
        ]);

        $response = $this->actingAs($customer)->post(route('bookings.store'), [
            'room_id' => $room->id,
            'stay_type' => 'nightly',
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'check_in_time' => '01:15',
            'check_out_time' => '23:45',
            'guests' => 2,
        ]);

        $booking = Booking::query()->firstOrFail();

        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertDatabaseHas('booking_guest_details', [
            'booking_id' => $booking->id,
            'stay_type' => 'nightly',
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
        ]);
    }

    public function test_registration_verification_stops_after_max_attempts(): void
    {
        $verification = RegistrationVerification::query()->create([
            'name' => 'Test User',
            'email' => 'verify@example.com',
            'phone' => '+639000000010',
            'otp_channel' => RegistrationVerification::OTP_CHANNEL_EMAIL,
            'password_encrypted' => Crypt::encryptString('password1234'),
            'code_hash' => Hash::make('123456'),
            'code_expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this
                ->withSession(['pending_registration_email' => $verification->email])
                ->post(route('register.verify.perform'), ['code' => '000000']);

            $response->assertSessionHasErrors('code');
        }

        $verification->refresh();
        $this->assertSame(5, $verification->attempts);

        $response = $this
            ->withSession(['pending_registration_email' => $verification->email])
            ->post(route('register.verify.perform'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');

        $verification->refresh();
        $this->assertSame(5, $verification->attempts);
    }
}

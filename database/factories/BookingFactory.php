<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Booking $booking): void {
            $guestTotal = fake()->numberBetween(1, max(1, (int) $booking->room->capacity));
            $booking->guestDetail()->create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'adults' => $guestTotal,
                'kids' => 0,
            ]);

            $isPaid = in_array($booking->status, ['confirmed', 'completed'], true);

            $booking->payment()->create([
                'amount' => $booking->total_price,
                'method' => $isPaid ? fake()->randomElement(['cash', 'bank_transfer', 'gcash', 'paymaya']) : 'pending',
                'status' => $isPaid ? 'paid' : 'unpaid',
                'transaction_reference' => $isPaid ? Payment::generateTransactionReference((int) $booking->id) : null,
                'paid_at' => $isPaid ? now() : null,
            ]);
        });
    }

    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 day', '+1 month');
        $checkOut = (clone $checkIn)->modify('+'.fake()->numberBetween(1, 7).' days');
        $status = fake()->randomElement(['pending', 'confirmed', 'cancelled', 'completed']);

        return [
            'customer_id' => Customer::factory(),
            'room_id' => Room::factory(),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
            'notes' => fake()->boolean(20) ? fake()->sentence() : null,
        ];
    }
}

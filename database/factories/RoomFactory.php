<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $types = ['Standard', 'Deluxe', 'Suite', 'Family'];
        $viewTypes = ['Nature View', 'Garden View', 'Pool View', 'Mountain View', 'Courtyard View'];
        $nightlyPrice = fake()->randomFloat(2, 60, 350);
        
        return [
            'name' => fake()->streetName().' Room',
            'type' => fake()->randomElement($types),
            'view_type' => fake()->randomElement($viewTypes),
            'description' => fake()->sentence(12),
            'price_per_night' => $nightlyPrice,
            'capacity' => fake()->numberBetween(1, 6),
            'image' => null,
            'room_status_id' => \App\Models\RoomStatus::inRandomOrder()->first()?->id,
        ];
    }
}

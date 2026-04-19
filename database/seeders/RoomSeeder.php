<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomStatus;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $roomStatusIds = RoomStatus::query()->pluck('room_status_id', 'slug');

        $sampleRooms = [
            [
                'name' => 'Room 101 - Garden Standard',
                'type' => 'Standard',
                'view_type' => 'Nature View',
                'description' => 'Comfortable standard room with garden-side windows and work desk.',
                'price_per_night' => 2599.00,
                'capacity' => 2,
                'image' => null,
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Room 102 - City Standard',
                'type' => 'Standard',
                'view_type' => 'Garden View',
                'description' => 'Bright standard room with lush garden-facing windows and fast Wi-Fi.',
                'price_per_night' => 2699.00,
                'capacity' => 2,
                'image' => null,
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Room 201 - Deluxe Twin',
                'type' => 'Deluxe',
                'view_type' => 'Pool View',
                'description' => 'Deluxe twin room with premium bedding and minibar.',
                'price_per_night' => 3499.00,
                'capacity' => 3,
                'image' => null,
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Room 202 - Deluxe King',
                'type' => 'Deluxe',
                'view_type' => 'Garden View',
                'description' => 'Spacious king room with lounge chair and rainfall shower.',
                'price_per_night' => 3799.00,
                'capacity' => 3,
                'image' => null,
                'room_status_slug' => 'being_cleaned',
            ],
            [
                'name' => 'Room 301 - Family Comfort',
                'type' => 'Family',
                'view_type' => 'Courtyard View',
                'description' => 'Family-friendly room with extra sleeping space and sofa bed.',
                'price_per_night' => 4599.00,
                'capacity' => 5,
                'image' => null,
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Room 302 - Family Plus',
                'type' => 'Family',
                'view_type' => 'Pool View',
                'description' => 'Large family room near elevator with kid-safe fixtures.',
                'price_per_night' => 4899.00,
                'capacity' => 6,
                'image' => null,
                'room_status_slug' => 'dirty',
            ],
            [
                'name' => 'Room 401 - Junior Suite',
                'type' => 'Suite',
                'view_type' => 'Nature View',
                'description' => 'Suite with separate sitting area and premium toiletries.',
                'price_per_night' => 5699.00,
                'capacity' => 4,
                'image' => null,
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Room 402 - Executive Suite',
                'type' => 'Executive',
                'view_type' => 'Mountain View',
                'description' => 'Executive suite with workstation, meeting nook, and mountain-facing windows.',
                'price_per_night' => 6999.00,
                'capacity' => 4,
                'image' => null,
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Room 501 - Penthouse East',
                'type' => 'Penthouse',
                'view_type' => 'Mountain View',
                'description' => 'Top-floor penthouse with private dining and panoramic windows.',
                'price_per_night' => 9999.00,
                'capacity' => 6,
                'image' => null,
                'room_status_slug' => 'dirty',
            ],
            [
                'name' => 'Room 502 - Penthouse West',
                'type' => 'Penthouse',
                'view_type' => 'Nature View',
                'description' => 'Luxury penthouse with sunset-facing balcony and lounge area.',
                'price_per_night' => 10499.00,
                'capacity' => 6,
                'image' => null,
                'room_status_slug' => 'clean',
            ],
        ];

        foreach ($sampleRooms as $room) {
            $roomStatusSlug = $room['room_status_slug'];

            unset($room['room_status_slug']);

            $room['room_status_id'] = $roomStatusIds[$roomStatusSlug] ?? null;

            Room::query()->updateOrCreate(
                ['name' => $room['name']],
                $room
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Clean', 'slug' => 'clean', 'description' => 'Room is clean and ready for guests'],
            ['name' => 'Dirty', 'slug' => 'dirty', 'description' => 'Room is dirty and needs deep cleaning'],
            ['name' => 'Make Up Room', 'slug' => 'being_cleaned', 'description' => 'Room is currently under make-up room service'],
            ['name' => 'Inspected', 'slug' => 'inspected', 'description' => 'Room has been inspected and approved'],
        ];

        DB::table('room_status')->insert($statuses);
    }
}

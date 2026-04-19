<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomStatusSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        $statuses = [
            ['name' => 'Clean', 'slug' => 'clean', 'description' => 'Room is clean and ready for guests', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'Dirty', 'slug' => 'dirty', 'description' => 'Room is dirty and needs deep cleaning', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'Make Up Room', 'slug' => 'being_cleaned', 'description' => 'Room is currently under make-up room service', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'Inspected', 'slug' => 'inspected', 'description' => 'Room has been inspected and approved', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ];

        DB::table('room_status')->upsert(
            $statuses,
            ['slug'],
            ['name', 'description', 'updated_at']
        );
    }
}

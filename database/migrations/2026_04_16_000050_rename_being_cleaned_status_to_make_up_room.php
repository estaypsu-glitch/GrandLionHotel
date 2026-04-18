<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('room_cleaning_statuses')) {
            return;
        }

        DB::table('room_cleaning_statuses')
            ->where('slug', 'being_cleaned')
            ->update([
                'name' => 'Make Up Room',
                'description' => 'Room is currently under make-up room cleaning service',
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('room_cleaning_statuses')) {
            return;
        }

        DB::table('room_cleaning_statuses')
            ->where('slug', 'being_cleaned')
            ->update([
                'name' => 'Being Cleaned',
                'description' => 'Room is currently being cleaned',
            ]);
    }
};

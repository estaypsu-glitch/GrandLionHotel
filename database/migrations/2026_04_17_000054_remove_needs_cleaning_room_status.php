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

        $needsCleaningStatusId = DB::table('room_cleaning_statuses')
            ->where('slug', 'needs_cleaning')
            ->value('cleaning_status_id');

        if ($needsCleaningStatusId === null) {
            return;
        }

        $dirtyStatusId = DB::table('room_cleaning_statuses')
            ->where('slug', 'dirty')
            ->value('cleaning_status_id');

        if ($dirtyStatusId !== null) {
            if (Schema::hasTable('rooms')) {
                DB::table('rooms')
                    ->where('cleaning_status_id', $needsCleaningStatusId)
                    ->update(['cleaning_status_id' => $dirtyStatusId]);
            }

            DB::table('room_cleaning_statuses')
                ->where('cleaning_status_id', $needsCleaningStatusId)
                ->delete();

            return;
        }

        DB::table('room_cleaning_statuses')
            ->where('cleaning_status_id', $needsCleaningStatusId)
            ->update([
                'name' => 'Dirty',
                'slug' => 'dirty',
                'description' => 'Room is dirty and needs deep cleaning',
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('room_cleaning_statuses')) {
            return;
        }

        $needsCleaningExists = DB::table('room_cleaning_statuses')
            ->where('slug', 'needs_cleaning')
            ->exists();

        if ($needsCleaningExists) {
            return;
        }

        DB::table('room_cleaning_statuses')->insert([
            'name' => 'Needs Cleaning',
            'slug' => 'needs_cleaning',
            'description' => 'Room needs cleaning before next guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};

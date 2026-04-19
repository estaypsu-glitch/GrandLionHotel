<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('room_status')) {
            return;
        }

        $timestamp = now();
        $hasCreatedAt = Schema::hasColumn('room_status', 'created_at');
        $hasUpdatedAt = Schema::hasColumn('room_status', 'updated_at');

        $cleanStatusId = DB::table('room_status')->where('slug', 'clean')->value('room_status_id');

        if (!$cleanStatusId) {
            $payload = [
                'name' => 'Clean',
                'slug' => 'clean',
                'description' => 'Room is clean and ready for guests',
            ];

            if ($hasCreatedAt) {
                $payload['created_at'] = $timestamp;
            }

            if ($hasUpdatedAt) {
                $payload['updated_at'] = $timestamp;
            }

            DB::table('room_status')->insert($payload);
            $cleanStatusId = DB::table('room_status')->where('slug', 'clean')->value('room_status_id');
        }

        if (!$cleanStatusId) {
            return;
        }

        $inspectedStatusId = DB::table('room_status')->where('slug', 'inspected')->value('room_status_id');

        if (!$inspectedStatusId) {
            return;
        }

        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'room_status_id')) {
            $roomUpdate = ['room_status_id' => $cleanStatusId];
            if (Schema::hasColumn('rooms', 'status_updated_at')) {
                $roomUpdate['status_updated_at'] = $timestamp;
            }

            DB::table('rooms')
                ->where('room_status_id', $inspectedStatusId)
                ->update($roomUpdate);
        }

        DB::table('room_status')
            ->where('room_status_id', $inspectedStatusId)
            ->delete();
    }

    public function down(): void
    {
        if (!Schema::hasTable('room_status')) {
            return;
        }

        $exists = DB::table('room_status')->where('slug', 'inspected')->exists();
        if ($exists) {
            return;
        }

        $payload = [
            'name' => 'Inspected',
            'slug' => 'inspected',
            'description' => 'Room has been inspected and approved',
        ];

        if (Schema::hasColumn('room_status', 'created_at')) {
            $payload['created_at'] = now();
        }

        if (Schema::hasColumn('room_status', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('room_status')->insert($payload);
    }
};


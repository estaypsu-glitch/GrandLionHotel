<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('rooms', 'rooms_room_status_id_foreign');

        if (Schema::hasTable('room_statuses') && !Schema::hasTable('room_status')) {
            Schema::rename('room_statuses', 'room_status');
        }

        $this->addRoomStatusForeignKey('room_status');
    }

    public function down(): void
    {
        $this->dropForeignIfExists('rooms', 'rooms_room_status_id_foreign');

        if (Schema::hasTable('room_status') && !Schema::hasTable('room_statuses')) {
            Schema::rename('room_status', 'room_statuses');
        }

        $this->addRoomStatusForeignKey('room_statuses');
    }

    private function addRoomStatusForeignKey(string $statusTable): void
    {
        if (
            !Schema::hasTable('rooms')
            || !Schema::hasTable($statusTable)
            || !Schema::hasColumn('rooms', 'room_status_id')
            || !Schema::hasColumn($statusTable, 'room_status_id')
        ) {
            return;
        }

        try {
            Schema::table('rooms', function (Blueprint $table) use ($statusTable): void {
                $table->foreign('room_status_id')
                    ->references('room_status_id')
                    ->on($statusTable)
                    ->nullOnDelete();
            });
        } catch (\Throwable) {
            // Keep migration resilient when key already exists on this driver.
        }
    }

    private function dropForeignIfExists(string $table, string $constraintName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($constraintName): void {
                $table->dropForeign($constraintName);
            });
        } catch (\Throwable) {
            // Ignore if the key is already absent.
        }
    }
};

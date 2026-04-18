<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        Schema::table('rooms', function (Blueprint $table) use ($statusTable): void {
            $table->foreign('room_status_id')
                ->references('room_status_id')
                ->on($statusTable)
                ->nullOnDelete();
        });
    }

    private function dropForeignIfExists(string $table, string $constraintName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $databaseName = DB::getDatabaseName();

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraintName));
        }
    }
};

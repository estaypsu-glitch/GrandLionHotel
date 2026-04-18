<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('rooms', 'rooms_cleaning_status_id_foreign');
        $this->dropForeignIfExists('rooms', 'rooms_room_status_id_foreign');

        if (Schema::hasTable('room_cleaning_statuses')) {
            $this->renameColumn('room_cleaning_statuses', 'cleaning_status_id', 'room_status_id', 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

            if (!Schema::hasTable('room_statuses')) {
                Schema::rename('room_cleaning_statuses', 'room_statuses');
            }
        }

        if (Schema::hasTable('rooms')) {
            $this->renameColumn('rooms', 'cleaning_status_id', 'room_status_id', 'BIGINT UNSIGNED NULL');
        }

        if (
            Schema::hasTable('rooms')
            && Schema::hasTable('room_statuses')
            && Schema::hasColumn('rooms', 'room_status_id')
            && Schema::hasColumn('room_statuses', 'room_status_id')
        ) {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->foreign('room_status_id')
                    ->references('room_status_id')
                    ->on('room_statuses')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $this->dropForeignIfExists('rooms', 'rooms_room_status_id_foreign');
        $this->dropForeignIfExists('rooms', 'rooms_cleaning_status_id_foreign');

        if (Schema::hasTable('rooms')) {
            $this->renameColumn('rooms', 'room_status_id', 'cleaning_status_id', 'BIGINT UNSIGNED NULL');
        }

        if (Schema::hasTable('room_statuses')) {
            $this->renameColumn('room_statuses', 'room_status_id', 'cleaning_status_id', 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

            if (!Schema::hasTable('room_cleaning_statuses')) {
                Schema::rename('room_statuses', 'room_cleaning_statuses');
            }
        }

        if (
            Schema::hasTable('rooms')
            && Schema::hasTable('room_cleaning_statuses')
            && Schema::hasColumn('rooms', 'cleaning_status_id')
            && Schema::hasColumn('room_cleaning_statuses', 'cleaning_status_id')
        ) {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->foreign('cleaning_status_id')
                    ->references('cleaning_status_id')
                    ->on('room_cleaning_statuses')
                    ->nullOnDelete();
            });
        }
    }

    private function renameColumn(string $table, string $from, string $to, string $definition): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` CHANGE `%s` `%s` %s',
            $table,
            $from,
            $to,
            $definition
        ));
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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bookings') || !Schema::hasColumn('bookings', 'handled_by_staff_id')) {
            return;
        }

        DB::table('bookings')
            ->whereNull('assigned_staff_id')
            ->whereNotNull('handled_by_staff_id')
            ->update([
                'assigned_staff_id' => DB::raw('handled_by_staff_id'),
            ]);

        $this->dropForeignIfExists('bookings', 'bookings_handled_by_staff_id_foreign');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('handled_by_staff_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bookings') || Schema::hasColumn('bookings', 'handled_by_staff_id')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('handled_by_staff_id')
                ->nullable()
                ->after('actual_check_out_at');
        });

        DB::table('bookings')
            ->whereNull('handled_by_staff_id')
            ->whereNotNull('assigned_staff_id')
            ->update([
                'handled_by_staff_id' => DB::raw('assigned_staff_id'),
            ]);

        if (!$this->foreignKeyExists('bookings', 'bookings_handled_by_staff_id_foreign')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->foreign('handled_by_staff_id')
                    ->references('staff_id')
                    ->on('staff')
                    ->nullOnDelete();
            });
        }
    }

    private function dropForeignIfExists(string $table, string $constraintName): void
    {
        if (!$this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraintName));
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};

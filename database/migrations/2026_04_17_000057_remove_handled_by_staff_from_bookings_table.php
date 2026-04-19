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

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('handled_by_staff_id');
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

        try {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->foreign('handled_by_staff_id')
                    ->references('staff_id')
                    ->on('staff')
                    ->nullOnDelete();
            });
        } catch (\Throwable) {
            // Ignore if the foreign key already exists.
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

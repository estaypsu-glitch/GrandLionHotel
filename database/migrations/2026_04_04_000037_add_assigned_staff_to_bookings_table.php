<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('assigned_staff_id')
                ->nullable()
                ->after('handled_by_staff_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['assigned_staff_id', 'status']);
        });

        DB::table('bookings')
            ->whereNull('assigned_staff_id')
            ->whereNotNull('handled_by_staff_id')
            ->update([
                'assigned_staff_id' => DB::raw('handled_by_staff_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['assigned_staff_id', 'status']);
            $table->dropConstrainedForeignId('assigned_staff_id');
        });
    }
};
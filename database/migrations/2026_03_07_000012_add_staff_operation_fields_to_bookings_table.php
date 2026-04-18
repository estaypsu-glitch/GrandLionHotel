<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('actual_check_in_at')->nullable()->after('notes');
            $table->timestamp('actual_check_out_at')->nullable()->after('actual_check_in_at');
            $table->foreignId('handled_by_staff_id')->nullable()->after('actual_check_out_at')
                ->constrained('users')->nullOnDelete();
            $table->text('staff_notes')->nullable()->after('handled_by_staff_id');

            $table->index(['status', 'check_in']);
            $table->index(['status', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status', 'check_in']);
            $table->dropIndex(['status', 'check_out']);
            $table->dropConstrainedForeignId('handled_by_staff_id');
            $table->dropColumn(['actual_check_in_at', 'actual_check_out_at', 'staff_notes']);
        });
    }
};

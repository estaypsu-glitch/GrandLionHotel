<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (!Schema::hasColumn('bookings', 'requested_check_in')) {
                $table->date('requested_check_in')->nullable()->after('check_out');
            }

            if (!Schema::hasColumn('bookings', 'requested_check_out')) {
                $table->date('requested_check_out')->nullable()->after('requested_check_in');
            }

            if (!Schema::hasColumn('bookings', 'reschedule_request_notes')) {
                $table->text('reschedule_request_notes')->nullable()->after('staff_notes');
            }

            if (!Schema::hasColumn('bookings', 'reschedule_requested_at')) {
                $table->timestamp('reschedule_requested_at')->nullable()->after('reschedule_request_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('bookings', 'reschedule_requested_at') ? 'reschedule_requested_at' : null,
                Schema::hasColumn('bookings', 'reschedule_request_notes') ? 'reschedule_request_notes' : null,
                Schema::hasColumn('bookings', 'requested_check_out') ? 'requested_check_out' : null,
                Schema::hasColumn('bookings', 'requested_check_in') ? 'requested_check_in' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

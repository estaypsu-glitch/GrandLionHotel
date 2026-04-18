<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $bookingGuestDetailColumns = array_values(array_filter([
            Schema::hasColumn('booking_guest_details', 'check_in_time') ? 'check_in_time' : null,
            Schema::hasColumn('booking_guest_details', 'check_out_time') ? 'check_out_time' : null,
        ]));

        if ($bookingGuestDetailColumns !== []) {
            Schema::table('booking_guest_details', function (Blueprint $table) use ($bookingGuestDetailColumns): void {
                $table->dropColumn($bookingGuestDetailColumns);
            });
        }

        if (Schema::hasColumn('users', 'salary_rate')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('salary_rate');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('booking_guest_details', 'check_in_time')
            || !Schema::hasColumn('booking_guest_details', 'check_out_time')) {
            Schema::table('booking_guest_details', function (Blueprint $table): void {
                if (!Schema::hasColumn('booking_guest_details', 'check_in_time')) {
                    $table->string('check_in_time', 10)->nullable()->after('postal_code');
                }

                if (!Schema::hasColumn('booking_guest_details', 'check_out_time')) {
                    $table->string('check_out_time', 10)->nullable()->after('check_in_time');
                }
            });
        }

        if (!Schema::hasColumn('users', 'salary_rate')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->decimal('salary_rate', 10, 2)->default(0)->after('salary_type');
            });
        }
    }
};

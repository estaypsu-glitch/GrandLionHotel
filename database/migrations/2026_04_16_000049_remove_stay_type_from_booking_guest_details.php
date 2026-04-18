<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('booking_guest_details', 'stay_type')) {
            return;
        }

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->dropColumn('stay_type');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('booking_guest_details', 'stay_type')) {
            return;
        }

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->string('stay_type', 20)->default('nightly')->after('postal_code');
        });
    }
};

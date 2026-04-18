<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Add booking_status_id
            $table->foreignId('booking_status_id')
                ->nullable()
                ->after('room_id')
                ->constrained('booking_statuses')
                ->nullOnDelete();

            // Add payment_status_id
            $table->foreignId('payment_status_id')
                ->nullable()
                ->after('booking_status_id')
                ->constrained('payment_statuses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_status_id');
            $table->dropConstrainedForeignId('payment_status_id');
        });
    }
};


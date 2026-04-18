<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add payment_method_id
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('booking_id')
                ->constrained('payment_methods')
                ->nullOnDelete();

            // Add payment_status_id (replace the status column logic)
            $table->foreignId('payment_status_id')
                ->nullable()
                ->after('payment_method_id')
                ->constrained('payment_statuses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropConstrainedForeignId('payment_status_id');
        });
    }
};


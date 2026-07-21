<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table): void {
            $table->bigIncrements('refund_request_id');
            $table->foreignId('booking_id')
                ->constrained('bookings', 'booking_id')
                ->cascadeOnDelete();
            $table->foreignId('payment_id')
                ->constrained('payments', 'payment_id')
                ->cascadeOnDelete();
            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('admins', 'admin_id')
                ->nullOnDelete();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers', 'customer_id')
                ->nullOnDelete();
            $table->foreignId('staff_id')
                ->nullable()
                ->constrained('staff', 'staff_id')
                ->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};

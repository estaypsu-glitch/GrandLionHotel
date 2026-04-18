<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('customer_reference', 120)->nullable()->after('qr_reference');
            $table->string('payment_proof_path')->nullable()->after('customer_reference');
            $table->timestamp('verified_at')->nullable()->after('paid_at');
            $table->foreignId('verified_by_staff_id')
                ->nullable()
                ->after('verified_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['status', 'verified_at'], 'payments_status_verified_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_status_verified_at_idx');
            $table->dropConstrainedForeignId('verified_by_staff_id');
            $table->dropColumn([
                'customer_reference',
                'payment_proof_path',
                'verified_at',
            ]);
        });
    }
};

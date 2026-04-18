<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateBookingIds = DB::table('payments')
            ->select('booking_id')
            ->whereNotNull('booking_id')
            ->groupBy('booking_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('booking_id');

        foreach ($duplicateBookingIds as $bookingId) {
            $paymentIds = DB::table('payments')
                ->where('booking_id', $bookingId)
                ->orderByDesc('paid_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->pluck('id');

            $paymentIdToKeep = $paymentIds->shift();

            if ($paymentIdToKeep && $paymentIds->isNotEmpty()) {
                DB::table('payments')
                    ->whereIn('id', $paymentIds->all())
                    ->delete();
            }
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->unique('booking_id', 'payments_booking_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_booking_id_unique');
        });
    }
};
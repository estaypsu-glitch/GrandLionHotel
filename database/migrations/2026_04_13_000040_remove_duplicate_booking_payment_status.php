<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bookings', 'payment_status')) {
            DB::table('bookings')
                ->select(['id', 'total_price', 'payment_status'])
                ->orderBy('id')
                ->chunkById(200, function ($bookings): void {
                    $now = Carbon::now();
                    $bookingIds = collect($bookings)->pluck('id')->all();

                    $existingPayments = DB::table('payments')
                        ->whereIn('booking_id', $bookingIds)
                        ->get(['id', 'booking_id', 'status'])
                        ->keyBy('booking_id');

                    $toInsert = [];

                    foreach ($bookings as $booking) {
                        $normalizedStatus = $this->normalizePaymentStatus($booking->payment_status);
                        $existingPayment = $existingPayments->get($booking->id);

                        if ($existingPayment) {
                            $existingStatus = $this->normalizePaymentStatus($existingPayment->status);
                            if ($existingStatus !== $normalizedStatus
                                && in_array($normalizedStatus, ['refund_pending', 'refunded'], true)) {
                                DB::table('payments')
                                    ->where('id', $existingPayment->id)
                                    ->update([
                                        'status' => $normalizedStatus,
                                        'updated_at' => $now,
                                    ]);
                            }

                            continue;
                        }

                        $toInsert[] = [
                            'booking_id' => $booking->id,
                            'amount' => $booking->total_price,
                            'method' => $normalizedStatus === 'unpaid' ? 'pending' : 'cash',
                            'status' => $normalizedStatus,
                            'paid_at' => $normalizedStatus === 'paid' ? $now : null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($toInsert !== []) {
                        DB::table('payments')->insert($toInsert);
                    }
                }, 'id');

            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropColumn('payment_status');
            });
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->index('status', 'payments_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('payment_status', 20)->default('unpaid')->after('status');
        });

        DB::table('bookings')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(200, function ($bookings): void {
                $bookingIds = collect($bookings)->pluck('id')->all();

                $paymentStatuses = DB::table('payments')
                    ->whereIn('booking_id', $bookingIds)
                    ->pluck('status', 'booking_id');

                foreach ($bookings as $booking) {
                    DB::table('bookings')
                        ->where('id', $booking->id)
                        ->update([
                            'payment_status' => $this->normalizePaymentStatus($paymentStatuses[$booking->id] ?? 'unpaid'),
                        ]);
                }
            }, 'id');

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_status_index');
        });
    }

    private function normalizePaymentStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return in_array($normalized, ['unpaid', 'paid', 'refund_pending', 'refunded'], true)
            ? $normalized
            : 'unpaid';
    }
};


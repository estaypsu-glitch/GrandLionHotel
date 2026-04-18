<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillGuestBreakdownFromLegacyGuestCount();
        $this->backfillPaymentsFromLegacyBookingTotal();

        if (Schema::hasColumn('rooms', 'price_per_hour') || Schema::hasColumn('rooms', 'is_available')) {
            Schema::table('rooms', function (Blueprint $table): void {
                if (Schema::hasColumn('rooms', 'price_per_hour')) {
                    $table->dropColumn('price_per_hour');
                }

                if (Schema::hasColumn('rooms', 'is_available')) {
                    $table->dropColumn('is_available');
                }
            });
        }

        if (Schema::hasColumn('bookings', 'guests') || Schema::hasColumn('bookings', 'total_price')) {
            Schema::table('bookings', function (Blueprint $table): void {
                if (Schema::hasColumn('bookings', 'guests')) {
                    $table->dropColumn('guests');
                }

                if (Schema::hasColumn('bookings', 'total_price')) {
                    $table->dropColumn('total_price');
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('rooms', 'price_per_hour') || !Schema::hasColumn('rooms', 'is_available')) {
            Schema::table('rooms', function (Blueprint $table): void {
                if (!Schema::hasColumn('rooms', 'price_per_hour')) {
                    $table->decimal('price_per_hour', 10, 2)->nullable()->after('price_per_night');
                }

                if (!Schema::hasColumn('rooms', 'is_available')) {
                    $table->boolean('is_available')->default(true)->after('capacity');
                }
            });
        }

        if (!Schema::hasColumn('bookings', 'guests') || !Schema::hasColumn('bookings', 'total_price')) {
            Schema::table('bookings', function (Blueprint $table): void {
                if (!Schema::hasColumn('bookings', 'guests')) {
                    $table->unsignedInteger('guests')->default(1)->after('check_out');
                }

                if (!Schema::hasColumn('bookings', 'total_price')) {
                    $table->decimal('total_price', 10, 2)->default(0)->after('guests');
                }
            });
        }

        $cleaningStatusById = DB::table('room_cleaning_statuses')->pluck('slug', 'id');

        DB::table('rooms')
            ->select(['id', 'cleaning_status_id', 'price_per_night'])
            ->orderBy('id')
            ->chunkById(200, function ($rooms) use ($cleaningStatusById): void {
                foreach ($rooms as $room) {
                    $slug = strtolower(trim((string) ($cleaningStatusById[$room->cleaning_status_id] ?? '')));
                    $isAvailable = in_array($slug, ['clean', 'inspected'], true);
                    $hourlyRate = round((float) $room->price_per_night / 12, 2);

                    DB::table('rooms')
                        ->where('id', $room->id)
                        ->update([
                            'price_per_hour' => $hourlyRate,
                            'is_available' => $isAvailable,
                        ]);
                }
            }, 'id');

        DB::table('bookings')
            ->select(['id', 'room_id', 'check_in', 'check_out'])
            ->orderBy('id')
            ->chunkById(200, function ($bookings): void {
                $bookingIds = collect($bookings)->pluck('id')->all();
                $roomIds = collect($bookings)->pluck('room_id')->filter()->unique()->all();

                $paymentsByBookingId = DB::table('payments')
                    ->whereIn('booking_id', $bookingIds)
                    ->pluck('amount', 'booking_id');

                $detailsByBookingId = DB::table('booking_guest_details')
                    ->whereIn('booking_id', $bookingIds)
                    ->get(['booking_id', 'adults', 'kids'])
                    ->keyBy('booking_id');

                $nightlyRateByRoomId = DB::table('rooms')
                    ->whereIn('id', $roomIds)
                    ->pluck('price_per_night', 'id');

                foreach ($bookings as $booking) {
                    $detail = $detailsByBookingId->get($booking->id);
                    $adults = max(0, (int) ($detail?->adults ?? 0));
                    $kids = max(0, (int) ($detail?->kids ?? 0));
                    $guests = max(1, $adults + $kids);

                    $totalPrice = $paymentsByBookingId[$booking->id] ?? null;
                    if (is_null($totalPrice)) {
                        $nightlyRate = (float) ($nightlyRateByRoomId[$booking->room_id] ?? 0);
                        $checkIn = Carbon::parse((string) $booking->check_in)->startOfDay();
                        $checkOut = Carbon::parse((string) $booking->check_out)->startOfDay();
                        $nights = max(1, $checkIn->diffInDays($checkOut));
                        $totalPrice = round($nightlyRate * $nights, 2);
                    }

                    DB::table('bookings')
                        ->where('id', $booking->id)
                        ->update([
                            'guests' => $guests,
                            'total_price' => round((float) $totalPrice, 2),
                        ]);
                }
            }, 'id');
    }

    private function backfillGuestBreakdownFromLegacyGuestCount(): void
    {
        if (!Schema::hasColumn('bookings', 'guests') || !Schema::hasTable('booking_guest_details')) {
            return;
        }

        DB::table('bookings')
            ->select(['id', 'guests'])
            ->orderBy('id')
            ->chunkById(200, function ($bookings): void {
                $now = now();
                $bookingIds = collect($bookings)->pluck('id')->all();

                $detailsByBookingId = DB::table('booking_guest_details')
                    ->whereIn('booking_id', $bookingIds)
                    ->get(['id', 'booking_id', 'adults', 'kids'])
                    ->keyBy('booking_id');

                foreach ($bookings as $booking) {
                    $guestTotal = max(1, (int) ($booking->guests ?? 1));
                    $detail = $detailsByBookingId->get($booking->id);

                    if (!$detail) {
                        DB::table('booking_guest_details')->insert([
                            'booking_id' => $booking->id,
                            'stay_type' => 'nightly',
                            'adults' => $guestTotal,
                            'kids' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                        continue;
                    }

                    if (!is_null($detail->adults) && !is_null($detail->kids)) {
                        continue;
                    }

                    $adults = is_null($detail->adults) ? $guestTotal : max(0, (int) $detail->adults);
                    $kids = is_null($detail->kids) ? 0 : max(0, (int) $detail->kids);

                    DB::table('booking_guest_details')
                        ->where('id', $detail->id)
                        ->update([
                            'adults' => $adults,
                            'kids' => $kids,
                            'updated_at' => $now,
                        ]);
                }
            }, 'id');
    }

    private function backfillPaymentsFromLegacyBookingTotal(): void
    {
        if (!Schema::hasColumn('bookings', 'total_price') || !Schema::hasTable('payments')) {
            return;
        }

        DB::table('bookings')
            ->select(['id', 'total_price'])
            ->orderBy('id')
            ->chunkById(200, function ($bookings): void {
                $now = now();
                $bookingIds = collect($bookings)->pluck('id')->all();

                $paymentsByBookingId = DB::table('payments')
                    ->whereIn('booking_id', $bookingIds)
                    ->get(['id', 'booking_id', 'amount'])
                    ->keyBy('booking_id');

                foreach ($bookings as $booking) {
                    $amount = round((float) ($booking->total_price ?? 0), 2);
                    $existingPayment = $paymentsByBookingId->get($booking->id);

                    if ($existingPayment) {
                        if ((float) $existingPayment->amount <= 0 && $amount > 0) {
                            DB::table('payments')
                                ->where('id', $existingPayment->id)
                                ->update([
                                    'amount' => $amount,
                                    'updated_at' => $now,
                                ]);
                        }
                        continue;
                    }

                    DB::table('payments')->insert([
                        'booking_id' => $booking->id,
                        'amount' => $amount,
                        'method' => 'pending',
                        'status' => 'unpaid',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }, 'id');
    }
};

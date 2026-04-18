<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('booking_discounts')) {
            Schema::create('booking_discounts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
                $table->string('discount_type', 30)->nullable();
                $table->string('discount_id', 80)->nullable();
                $table->string('discount_id_photo_path')->nullable();
                $table->timestamps();
            });
        }

        $this->backfillBookingDiscounts();

        if (Schema::hasColumn('password_reset_tokens', 'user_id')) {
            Schema::table('password_reset_tokens', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasColumn('registration_verifications', 'user_id')) {
            Schema::table('registration_verifications', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        $guestDetailDiscountColumns = array_values(array_filter([
            Schema::hasColumn('booking_guest_details', 'discount_type') ? 'discount_type' : null,
            Schema::hasColumn('booking_guest_details', 'discount_id') ? 'discount_id' : null,
            Schema::hasColumn('booking_guest_details', 'discount_id_photo_path') ? 'discount_id_photo_path' : null,
        ]));

        if ($guestDetailDiscountColumns !== []) {
            Schema::table('booking_guest_details', function (Blueprint $table) use ($guestDetailDiscountColumns): void {
                $table->dropColumn($guestDetailDiscountColumns);
            });
        }

        $paymentDiscountColumns = array_values(array_filter([
            Schema::hasColumn('payments', 'discount_type') ? 'discount_type' : null,
            Schema::hasColumn('payments', 'discount_id') ? 'discount_id' : null,
            Schema::hasColumn('payments', 'discount_id_photo_path') ? 'discount_id_photo_path' : null,
        ]));

        if ($paymentDiscountColumns !== []) {
            Schema::table('payments', function (Blueprint $table) use ($paymentDiscountColumns): void {
                $table->dropColumn($paymentDiscountColumns);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('password_reset_tokens', 'user_id')) {
            Schema::table('password_reset_tokens', function (Blueprint $table): void {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('email')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('registration_verifications', 'user_id')) {
            Schema::table('registration_verifications', function (Blueprint $table): void {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('booking_guest_details', 'discount_type')
            || !Schema::hasColumn('booking_guest_details', 'discount_id')
            || !Schema::hasColumn('booking_guest_details', 'discount_id_photo_path')) {
            Schema::table('booking_guest_details', function (Blueprint $table): void {
                if (!Schema::hasColumn('booking_guest_details', 'discount_type')) {
                    $table->string('discount_type', 30)->nullable()->after('payment_preference');
                }
                if (!Schema::hasColumn('booking_guest_details', 'discount_id')) {
                    $table->string('discount_id', 80)->nullable()->after('discount_type');
                }
                if (!Schema::hasColumn('booking_guest_details', 'discount_id_photo_path')) {
                    $table->string('discount_id_photo_path')->nullable()->after('discount_id');
                }
            });
        }

        if (!Schema::hasColumn('payments', 'discount_type')
            || !Schema::hasColumn('payments', 'discount_id')
            || !Schema::hasColumn('payments', 'discount_id_photo_path')) {
            Schema::table('payments', function (Blueprint $table): void {
                if (!Schema::hasColumn('payments', 'discount_type')) {
                    $table->string('discount_type', 30)->nullable()->after('original_amount');
                }
                if (!Schema::hasColumn('payments', 'discount_id')) {
                    $table->string('discount_id', 80)->nullable()->after('discount_amount');
                }
                if (!Schema::hasColumn('payments', 'discount_id_photo_path')) {
                    $table->string('discount_id_photo_path')->nullable()->after('discount_id');
                }
            });
        }

        if (Schema::hasTable('booking_discounts')) {
            DB::table('booking_discounts')
                ->select(['id', 'booking_id', 'discount_type', 'discount_id', 'discount_id_photo_path'])
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('booking_guest_details')
                            ->where('booking_id', $row->booking_id)
                            ->update([
                                'discount_type' => $row->discount_type,
                                'discount_id' => $row->discount_id,
                                'discount_id_photo_path' => $row->discount_id_photo_path,
                            ]);

                        DB::table('payments')
                            ->where('booking_id', $row->booking_id)
                            ->update([
                                'discount_type' => $row->discount_type,
                                'discount_id' => $row->discount_id,
                                'discount_id_photo_path' => $row->discount_id_photo_path,
                            ]);
                    }
                }, 'id');

            Schema::dropIfExists('booking_discounts');
        }

        DB::statement("
            UPDATE password_reset_tokens prt
            INNER JOIN users u ON u.email = prt.email
            SET prt.user_id = u.id
            WHERE prt.user_id IS NULL
        ");

        DB::statement("
            UPDATE registration_verifications rv
            INNER JOIN users u ON u.email = rv.email
            SET rv.user_id = u.id
            WHERE rv.user_id IS NULL
        ");
    }

    private function backfillBookingDiscounts(): void
    {
        $paymentHasDiscountColumns = Schema::hasColumn('payments', 'discount_type')
            && Schema::hasColumn('payments', 'discount_id')
            && Schema::hasColumn('payments', 'discount_id_photo_path');

        DB::table('bookings')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(200, function ($bookings) use ($paymentHasDiscountColumns): void {
                $now = now();
                $bookingIds = collect($bookings)->pluck('id')->all();

                $guestDetails = DB::table('booking_guest_details')
                    ->whereIn('booking_id', $bookingIds)
                    ->get(['booking_id', 'discount_type', 'discount_id', 'discount_id_photo_path'])
                    ->keyBy('booking_id');

                $payments = $paymentHasDiscountColumns
                    ? DB::table('payments')
                        ->whereIn('booking_id', $bookingIds)
                        ->get(['booking_id', 'discount_type', 'discount_id', 'discount_id_photo_path'])
                        ->keyBy('booking_id')
                    : collect();

                $payload = [];

                foreach ($bookingIds as $bookingId) {
                    $guestDetail = $guestDetails->get($bookingId);
                    $payment = $payments->get($bookingId);

                    $discountType = $this->firstFilled(
                        $guestDetail->discount_type ?? null,
                        $payment->discount_type ?? null
                    );
                    $discountId = $this->firstFilled(
                        $guestDetail->discount_id ?? null,
                        $payment->discount_id ?? null
                    );
                    $discountProofPath = $this->firstFilled(
                        $guestDetail->discount_id_photo_path ?? null,
                        $payment->discount_id_photo_path ?? null
                    );

                    if ($discountType === null && $discountId === null && $discountProofPath === null) {
                        continue;
                    }

                    $payload[] = [
                        'booking_id' => $bookingId,
                        'discount_type' => $discountType,
                        'discount_id' => $discountId,
                        'discount_id_photo_path' => $discountProofPath,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($payload !== []) {
                    DB::table('booking_discounts')->upsert(
                        $payload,
                        ['booking_id'],
                        ['discount_type', 'discount_id', 'discount_id_photo_path', 'updated_at']
                    );
                }
            }, 'id');
    }

    private function firstFilled(?string ...$values): ?string
    {
        foreach ($values as $value) {
            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }
};

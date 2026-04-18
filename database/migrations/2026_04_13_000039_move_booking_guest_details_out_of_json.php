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
        Schema::create('booking_guest_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('street_address')->nullable();
            $table->string('street_address_line_2')->nullable();
            $table->string('guest_city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('check_in_time', 10)->nullable();
            $table->string('check_out_time', 10)->nullable();
            $table->unsignedInteger('adults')->nullable();
            $table->unsignedInteger('kids')->nullable();
            $table->string('payment_preference', 30)->nullable();
            $table->string('discount_type', 30)->nullable();
            $table->string('discount_id', 80)->nullable();
            $table->string('discount_id_photo_path')->nullable();
            $table->foreignId('created_by_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (Schema::hasColumn('bookings', 'reservation_meta')) {
            DB::table('bookings')
                ->select(['id', 'reservation_meta'])
                ->whereNotNull('reservation_meta')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    $now = Carbon::now();
                    $payload = [];

                    foreach ($rows as $row) {
                        $meta = json_decode((string) $row->reservation_meta, true);
                        if (!is_array($meta)) {
                            continue;
                        }

                        $detail = array_filter([
                            'booking_id' => $row->id,
                            'customer_name' => data_get($meta, 'customer_name'),
                            'customer_email' => data_get($meta, 'customer_email'),
                            'customer_phone' => data_get($meta, 'customer_phone'),
                            'first_name' => data_get($meta, 'first_name'),
                            'last_name' => data_get($meta, 'last_name'),
                            'street_address' => data_get($meta, 'street_address'),
                            'street_address_line_2' => data_get($meta, 'street_address_line_2'),
                            'guest_city' => data_get($meta, 'guest_city'),
                            'state_province' => data_get($meta, 'state_province'),
                            'postal_code' => data_get($meta, 'postal_code'),
                            'contact_phone' => data_get($meta, 'contact_phone'),
                            'contact_email' => data_get($meta, 'contact_email'),
                            'check_in_time' => data_get($meta, 'check_in_time'),
                            'check_out_time' => data_get($meta, 'check_out_time'),
                            'adults' => data_get($meta, 'adults'),
                            'kids' => data_get($meta, 'kids'),
                            'payment_preference' => data_get($meta, 'payment_preference'),
                            'discount_type' => data_get($meta, 'discount_type'),
                            'discount_id' => data_get($meta, 'discount_id'),
                            'discount_id_photo_path' => data_get($meta, 'discount_id_photo_path'),
                            'created_by_staff_id' => data_get($meta, 'created_by_staff_id'),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ], static fn ($value, $key): bool => in_array($key, ['booking_id', 'created_at', 'updated_at'], true)
                            || (!is_null($value) && $value !== ''), ARRAY_FILTER_USE_BOTH);

                        if (count($detail) > 3) {
                            $payload[] = $detail;
                        }
                    }

                    if ($payload !== []) {
                        DB::table('booking_guest_details')->upsert(
                            $payload,
                            ['booking_id'],
                            [
                                'customer_name',
                                'customer_email',
                                'customer_phone',
                                'first_name',
                                'last_name',
                                'street_address',
                                'street_address_line_2',
                                'guest_city',
                                'state_province',
                                'postal_code',
                                'contact_phone',
                                'contact_email',
                                'check_in_time',
                                'check_out_time',
                                'adults',
                                'kids',
                                'payment_preference',
                                'discount_type',
                                'discount_id',
                                'discount_id_photo_path',
                                'created_by_staff_id',
                                'updated_at',
                            ]
                        );
                    }
                }, 'id');

            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropColumn('reservation_meta');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->json('reservation_meta')->nullable()->after('notes');
        });

        DB::table('booking_guest_details')
            ->select('*')
            ->orderBy('booking_id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $meta = array_filter([
                        'customer_name' => $row->customer_name,
                        'customer_email' => $row->customer_email,
                        'customer_phone' => $row->customer_phone,
                        'first_name' => $row->first_name,
                        'last_name' => $row->last_name,
                        'street_address' => $row->street_address,
                        'street_address_line_2' => $row->street_address_line_2,
                        'guest_city' => $row->guest_city,
                        'state_province' => $row->state_province,
                        'postal_code' => $row->postal_code,
                        'contact_phone' => $row->contact_phone,
                        'contact_email' => $row->contact_email,
                        'check_in_time' => $row->check_in_time,
                        'check_out_time' => $row->check_out_time,
                        'adults' => $row->adults,
                        'kids' => $row->kids,
                        'payment_preference' => $row->payment_preference,
                        'discount_type' => $row->discount_type,
                        'discount_id' => $row->discount_id,
                        'discount_id_photo_path' => $row->discount_id_photo_path,
                        'created_by_staff_id' => $row->created_by_staff_id,
                    ], static fn ($value): bool => !is_null($value) && $value !== '');

                    DB::table('bookings')
                        ->where('id', $row->booking_id)
                        ->update([
                            'reservation_meta' => $meta !== [] ? json_encode($meta) : null,
                        ]);
                }
            }, 'id');

        Schema::dropIfExists('booking_guest_details');
    }
};


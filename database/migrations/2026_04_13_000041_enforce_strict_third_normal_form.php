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
        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('last_name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('address_line')->nullable()->after('phone');
            $table->string('city')->nullable()->after('street_address_line_2');
            $table->string('province')->nullable()->after('city');
        });

        DB::table('booking_guest_details')
            ->select([
                'id',
                'customer_name',
                'customer_email',
                'customer_phone',
                'first_name',
                'last_name',
                'street_address',
                'guest_city',
                'state_province',
                'contact_phone',
                'contact_email',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $firstName = trim((string) ($row->first_name ?? ''));
                    $lastName = trim((string) ($row->last_name ?? ''));
                    $customerName = trim((string) ($row->customer_name ?? ''));

                    if ($firstName === '' && $customerName !== '') {
                        $firstName = $customerName;
                    }

                    $email = trim((string) ($row->contact_email ?? ''));
                    if ($email === '') {
                        $email = trim((string) ($row->customer_email ?? ''));
                    }

                    $phone = trim((string) ($row->contact_phone ?? ''));
                    if ($phone === '') {
                        $phone = trim((string) ($row->customer_phone ?? ''));
                    }

                    DB::table('booking_guest_details')
                        ->where('id', $row->id)
                        ->update([
                            'first_name' => $firstName !== '' ? $firstName : null,
                            'last_name' => $lastName !== '' ? $lastName : null,
                            'email' => $email !== '' ? $email : null,
                            'phone' => $phone !== '' ? $phone : null,
                            'address_line' => $row->street_address,
                            'city' => $row->guest_city,
                            'province' => $row->state_province,
                        ]);
                }
            }, 'id');

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_name',
                'customer_email',
                'customer_phone',
                'street_address',
                'guest_city',
                'state_province',
                'contact_phone',
                'contact_email',
            ]);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('source', 20)->nullable()->after('status');
            $table->string('qr_reference', 80)->nullable()->after('source');
            $table->decimal('original_amount', 10, 2)->nullable()->after('qr_reference');
            $table->string('discount_type', 30)->nullable()->after('original_amount');
            $table->decimal('discount_rate', 5, 4)->nullable()->after('discount_type');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('discount_rate');
            $table->string('discount_id', 80)->nullable()->after('discount_amount');
            $table->string('discount_id_photo_path')->nullable()->after('discount_id');
        });

        if (Schema::hasColumn('payments', 'meta')) {
            DB::table('payments')
                ->select(['id', 'meta'])
                ->whereNotNull('meta')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $meta = json_decode((string) $row->meta, true);
                        if (!is_array($meta)) {
                            continue;
                        }

                        $discountType = trim((string) data_get($meta, 'discount_type', ''));
                        if ($discountType === 'none') {
                            $discountType = '';
                        }

                        DB::table('payments')
                            ->where('id', $row->id)
                            ->update([
                                'source' => data_get($meta, 'source'),
                                'qr_reference' => data_get($meta, 'qr_reference'),
                                'original_amount' => data_get($meta, 'original_amount'),
                                'discount_type' => $discountType !== '' ? $discountType : null,
                                'discount_rate' => data_get($meta, 'discount_rate'),
                                'discount_amount' => data_get($meta, 'discount_amount'),
                                'discount_id' => data_get($meta, 'discount_id'),
                                'discount_id_photo_path' => data_get($meta, 'discount_id_photo_path'),
                            ]);
                    }
                }, 'id');

            Schema::table('payments', function (Blueprint $table): void {
                $table->dropColumn('meta');
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->json('meta')->nullable()->after('paid_at');
        });

        DB::table('payments')
            ->select([
                'id',
                'source',
                'qr_reference',
                'original_amount',
                'discount_type',
                'discount_rate',
                'discount_amount',
                'discount_id',
                'discount_id_photo_path',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $meta = array_filter([
                        'source' => $row->source,
                        'qr_reference' => $row->qr_reference,
                        'original_amount' => $row->original_amount,
                        'discount_type' => $row->discount_type,
                        'discount_rate' => $row->discount_rate,
                        'discount_amount' => $row->discount_amount,
                        'discount_id' => $row->discount_id,
                        'discount_id_photo_path' => $row->discount_id_photo_path,
                    ], static fn ($value): bool => !is_null($value) && $value !== '');

                    DB::table('payments')
                        ->where('id', $row->id)
                        ->update([
                            'meta' => $meta !== [] ? json_encode($meta) : null,
                        ]);
                }
            }, 'id');

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn([
                'source',
                'qr_reference',
                'original_amount',
                'discount_type',
                'discount_rate',
                'discount_amount',
                'discount_id',
                'discount_id_photo_path',
            ]);
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->string('customer_name')->nullable()->after('booking_id');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('customer_phone', 30)->nullable()->after('customer_email');
            $table->string('street_address')->nullable()->after('last_name');
            $table->string('guest_city')->nullable()->after('street_address_line_2');
            $table->string('state_province')->nullable()->after('guest_city');
            $table->string('contact_phone', 30)->nullable()->after('postal_code');
            $table->string('contact_email')->nullable()->after('contact_phone');
        });

        DB::table('booking_guest_details')
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone',
                'address_line',
                'city',
                'province',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $name = trim(trim((string) $row->first_name).' '.trim((string) $row->last_name));

                    DB::table('booking_guest_details')
                        ->where('id', $row->id)
                        ->update([
                            'customer_name' => $name !== '' ? $name : null,
                            'customer_email' => $row->email,
                            'customer_phone' => $row->phone,
                            'street_address' => $row->address_line,
                            'guest_city' => $row->city,
                            'state_province' => $row->province,
                            'contact_phone' => $row->phone,
                            'contact_email' => $row->email,
                        ]);
                }
            }, 'id');

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->dropColumn([
                'email',
                'phone',
                'address_line',
                'city',
                'province',
            ]);
        });
    }
};

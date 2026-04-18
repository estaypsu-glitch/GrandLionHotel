<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('rooms', 'price_per_hour')) {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->decimal('price_per_hour', 10, 2)->nullable()->after('price_per_night');
            });
        }

        DB::table('rooms')
            ->select(['id', 'price_per_night', 'price_per_hour'])
            ->whereNull('price_per_hour')
            ->orderBy('id')
            ->chunkById(200, function ($rooms): void {
                foreach ($rooms as $room) {
                    $nightly = (float) $room->price_per_night;
                    $hourly = $nightly > 0 ? round($nightly / 12, 2) : 0;

                    DB::table('rooms')
                        ->where('id', $room->id)
                        ->update(['price_per_hour' => $hourly]);
                }
            });

        if (!Schema::hasColumn('booking_guest_details', 'stay_type')) {
            Schema::table('booking_guest_details', function (Blueprint $table): void {
                $table->string('stay_type', 20)->default('nightly')->after('check_out_time');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('booking_guest_details', 'stay_type')) {
            Schema::table('booking_guest_details', function (Blueprint $table): void {
                $table->dropColumn('stay_type');
            });
        }

        if (Schema::hasColumn('rooms', 'price_per_hour')) {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->dropColumn('price_per_hour');
            });
        }
    }
};


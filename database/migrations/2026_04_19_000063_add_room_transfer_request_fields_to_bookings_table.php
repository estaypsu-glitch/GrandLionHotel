<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (!Schema::hasColumn('bookings', 'room_transfer_request_reason')) {
                $table->text('room_transfer_request_reason')->nullable();
            }

            if (!Schema::hasColumn('bookings', 'room_transfer_requested_at')) {
                $table->timestamp('room_transfer_requested_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('bookings', 'room_transfer_requested_at') ? 'room_transfer_requested_at' : null,
                Schema::hasColumn('bookings', 'room_transfer_request_reason') ? 'room_transfer_request_reason' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('staff', 'staff_created_by_admin_id_foreign');
        $this->dropForeignIfExists('rooms', 'rooms_cleaning_status_id_foreign');
        $this->dropForeignIfExists('rooms', 'rooms_last_cleaned_by_foreign');
        $this->dropForeignIfExists('bookings', 'bookings_customer_id_foreign');
        $this->dropForeignIfExists('bookings', 'bookings_room_id_foreign');
        $this->dropForeignIfExists('bookings', 'bookings_handled_by_staff_id_foreign');
        $this->dropForeignIfExists('bookings', 'bookings_assigned_staff_id_foreign');
        $this->dropForeignIfExists('booking_guest_details', 'booking_guest_details_booking_id_foreign');
        $this->dropForeignIfExists('booking_guest_details', 'booking_guest_details_created_by_staff_id_foreign');
        $this->dropForeignIfExists('booking_discounts', 'booking_discounts_booking_id_foreign');
        $this->dropForeignIfExists('payments', 'payments_booking_id_foreign');
        $this->dropForeignIfExists('payments', 'payments_verified_by_staff_id_foreign');

        $this->renameAutoIncrementPrimaryKey('admins', 'id', 'admin_id');
        $this->renameAutoIncrementPrimaryKey('staff', 'id', 'staff_id');
        $this->renameAutoIncrementPrimaryKey('customers', 'id', 'customer_id');
        $this->renameAutoIncrementPrimaryKey('room_cleaning_statuses', 'id', 'cleaning_status_id');
        $this->renameAutoIncrementPrimaryKey('rooms', 'id', 'room_id');
        $this->renameColumn('rooms', 'last_cleaned_by', 'last_cleaned_by_staff_id', 'BIGINT UNSIGNED NULL');
        $this->renameAutoIncrementPrimaryKey('bookings', 'id', 'booking_id');
        $this->renameAutoIncrementPrimaryKey('booking_guest_details', 'id', 'guest_detail_id');
        $this->renameAutoIncrementPrimaryKey('booking_discounts', 'id', 'booking_discount_id');
        $this->renameAutoIncrementPrimaryKey('payments', 'id', 'payment_id');

        Schema::table('staff', function (Blueprint $table): void {
            $table->foreign('created_by_admin_id')
                ->references('admin_id')
                ->on('admins')
                ->nullOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->foreign('cleaning_status_id')
                ->references('cleaning_status_id')
                ->on('room_cleaning_statuses')
                ->nullOnDelete();

            $table->foreign('last_cleaned_by_staff_id')
                ->references('staff_id')
                ->on('staff')
                ->nullOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreign('customer_id')
                ->references('customer_id')
                ->on('customers')
                ->nullOnDelete();

            $table->foreign('room_id')
                ->references('room_id')
                ->on('rooms')
                ->cascadeOnDelete();

            $table->foreign('handled_by_staff_id')
                ->references('staff_id')
                ->on('staff')
                ->nullOnDelete();

            $table->foreign('assigned_staff_id')
                ->references('staff_id')
                ->on('staff')
                ->nullOnDelete();
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->foreign('booking_id')
                ->references('booking_id')
                ->on('bookings')
                ->cascadeOnDelete();

            $table->foreign('created_by_staff_id')
                ->references('staff_id')
                ->on('staff')
                ->nullOnDelete();
        });

        Schema::table('booking_discounts', function (Blueprint $table): void {
            $table->foreign('booking_id')
                ->references('booking_id')
                ->on('bookings')
                ->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreign('booking_id')
                ->references('booking_id')
                ->on('bookings')
                ->cascadeOnDelete();

            $table->foreign('verified_by_staff_id')
                ->references('staff_id')
                ->on('staff')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $this->dropForeignIfExists('staff', 'staff_created_by_admin_id_foreign');
        $this->dropForeignIfExists('rooms', 'rooms_cleaning_status_id_foreign');
        $this->dropForeignIfExists('rooms', 'rooms_last_cleaned_by_staff_id_foreign');
        $this->dropForeignIfExists('bookings', 'bookings_customer_id_foreign');
        $this->dropForeignIfExists('bookings', 'bookings_room_id_foreign');
        $this->dropForeignIfExists('bookings', 'bookings_handled_by_staff_id_foreign');
        $this->dropForeignIfExists('bookings', 'bookings_assigned_staff_id_foreign');
        $this->dropForeignIfExists('booking_guest_details', 'booking_guest_details_booking_id_foreign');
        $this->dropForeignIfExists('booking_guest_details', 'booking_guest_details_created_by_staff_id_foreign');
        $this->dropForeignIfExists('booking_discounts', 'booking_discounts_booking_id_foreign');
        $this->dropForeignIfExists('payments', 'payments_booking_id_foreign');
        $this->dropForeignIfExists('payments', 'payments_verified_by_staff_id_foreign');

        $this->renameAutoIncrementPrimaryKey('admins', 'admin_id', 'id');
        $this->renameAutoIncrementPrimaryKey('staff', 'staff_id', 'id');
        $this->renameAutoIncrementPrimaryKey('customers', 'customer_id', 'id');
        $this->renameAutoIncrementPrimaryKey('room_cleaning_statuses', 'cleaning_status_id', 'id');
        $this->renameAutoIncrementPrimaryKey('rooms', 'room_id', 'id');
        $this->renameColumn('rooms', 'last_cleaned_by_staff_id', 'last_cleaned_by', 'BIGINT UNSIGNED NULL');
        $this->renameAutoIncrementPrimaryKey('bookings', 'booking_id', 'id');
        $this->renameAutoIncrementPrimaryKey('booking_guest_details', 'guest_detail_id', 'id');
        $this->renameAutoIncrementPrimaryKey('booking_discounts', 'booking_discount_id', 'id');
        $this->renameAutoIncrementPrimaryKey('payments', 'payment_id', 'id');

        Schema::table('staff', function (Blueprint $table): void {
            $table->foreign('created_by_admin_id')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->foreign('cleaning_status_id')
                ->references('id')
                ->on('room_cleaning_statuses')
                ->nullOnDelete();

            $table->foreign('last_cleaned_by')
                ->references('id')
                ->on('staff')
                ->nullOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->nullOnDelete();

            $table->foreign('room_id')
                ->references('id')
                ->on('rooms')
                ->cascadeOnDelete();

            $table->foreign('handled_by_staff_id')
                ->references('id')
                ->on('staff')
                ->nullOnDelete();

            $table->foreign('assigned_staff_id')
                ->references('id')
                ->on('staff')
                ->nullOnDelete();
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->cascadeOnDelete();

            $table->foreign('created_by_staff_id')
                ->references('id')
                ->on('staff')
                ->nullOnDelete();
        });

        Schema::table('booking_discounts', function (Blueprint $table): void {
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->cascadeOnDelete();

            $table->foreign('verified_by_staff_id')
                ->references('id')
                ->on('staff')
                ->nullOnDelete();
        });
    }

    private function renameAutoIncrementPrimaryKey(string $table, string $from, string $to): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($from, $to): void {
            $table->renameColumn($from, $to);
        });
    }

    private function renameColumn(string $table, string $from, string $to, string $definition): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($from, $to): void {
            $table->renameColumn($from, $to);
        });
    }

    private function dropForeignIfExists(string $table, string $constraintName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($constraintName): void {
                $table->dropForeign($constraintName);
            });
        } catch (\Throwable) {
            // Ignore if missing on this driver/schema state.
        }
    }
};

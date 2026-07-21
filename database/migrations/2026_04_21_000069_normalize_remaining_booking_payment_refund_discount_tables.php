<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_date_discounts', function (Blueprint $table): void {
            if (!Schema::hasColumn('room_date_discounts', 'discount_date_start')) {
                $table->date('discount_date_start')->nullable()->after('room_id');
            }

            if (!Schema::hasColumn('room_date_discounts', 'discount_date_end')) {
                $table->date('discount_date_end')->nullable()->after('discount_date_start');
            }
        });

        if (Schema::hasColumn('room_date_discounts', 'discount_date')) {
            DB::table('room_date_discounts')->whereNull('discount_date_start')->update([
                'discount_date_start' => DB::raw('discount_date'),
                'discount_date_end' => DB::raw('discount_date'),
            ]);
        }

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            $table->index('room_id', 'room_date_discounts_room_id_index');

            if (Schema::hasColumn('room_date_discounts', 'discount_date')) {
                $this->dropIndexIfExists($table, 'room_date_discounts_room_date_unique', 'unique');
                $this->dropIndexIfExists($table, 'room_date_discounts_discount_date_index');
            }

            if (Schema::hasColumn('room_date_discounts', 'admin_id')) {
                $this->dropForeignIfExists($table, 'room_date_discounts_admin_id_foreign');
            }
        });

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            if (Schema::hasColumn('room_date_discounts', 'discount_date')) {
                $table->dropColumn('discount_date');
            }

            if (Schema::hasColumn('room_date_discounts', 'admin_id')) {
                $table->dropColumn('admin_id');
            }
        });

        DB::table('room_date_discounts')
            ->whereNull('discount_date_start')
            ->update(['discount_date_start' => DB::raw('discount_date_end')]);

        DB::table('room_date_discounts')
            ->whereNull('discount_date_end')
            ->update(['discount_date_end' => DB::raw('discount_date_start')]);

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            $table->date('discount_date_start')->nullable(false)->change();
            $table->date('discount_date_end')->nullable(false)->change();
            $table->unique(['room_id', 'discount_date_start', 'discount_date_end'], 'room_date_discounts_room_range_unique');
            $table->index(['discount_date_start', 'discount_date_end'], 'room_date_discounts_range_index');
        });

        Schema::table('refund_requests', function (Blueprint $table): void {
            $this->dropForeignIfExists($table, 'refund_requests_booking_id_foreign');
            $this->dropForeignIfExists($table, 'refund_requests_admin_id_foreign');
            $this->dropForeignIfExists($table, 'refund_requests_customer_id_foreign');
            $this->dropForeignIfExists($table, 'refund_requests_staff_id_foreign');
        });

        Schema::table('refund_requests', function (Blueprint $table): void {
            foreach (['booking_id', 'admin_id', 'customer_id', 'staff_id'] as $column) {
                if (Schema::hasColumn('refund_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            $this->dropForeignIfExists($table, 'payments_staff_id_foreign');
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $this->dropForeignIfExists($table, 'booking_guest_details_staff_id_foreign');
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_guest_details', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_guest_details', function (Blueprint $table): void {
            if (!Schema::hasColumn('booking_guest_details', 'staff_id')) {
                $table->foreignId('staff_id')->nullable()->after('payment_preference')
                    ->constrained('staff', 'staff_id')
                    ->nullOnDelete();
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('payments', 'staff_id')) {
                $table->foreignId('staff_id')->nullable()->after('verified_at')
                    ->constrained('staff', 'staff_id')
                    ->nullOnDelete();
            }
        });

        Schema::table('refund_requests', function (Blueprint $table): void {
            if (!Schema::hasColumn('refund_requests', 'booking_id')) {
                $table->foreignId('booking_id')->after('refund_request_id')
                    ->constrained('bookings', 'booking_id')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('refund_requests', 'admin_id')) {
                $table->foreignId('admin_id')->nullable()->after('payment_id')
                    ->constrained('admins', 'admin_id')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('refund_requests', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('admin_id')
                    ->constrained('customers', 'customer_id')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('refund_requests', 'staff_id')) {
                $table->foreignId('staff_id')->nullable()->after('customer_id')
                    ->constrained('staff', 'staff_id')
                    ->nullOnDelete();
            }
        });

        DB::table('refund_requests')
            ->join('payments', 'payments.payment_id', '=', 'refund_requests.payment_id')
            ->update(['refund_requests.booking_id' => DB::raw('payments.booking_id')]);

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            $this->dropIndexIfExists($table, 'room_date_discounts_room_range_unique', 'unique');
            $this->dropIndexIfExists($table, 'room_date_discounts_range_index');

            if (!Schema::hasColumn('room_date_discounts', 'discount_date')) {
                $table->date('discount_date')->nullable()->after('room_id');
            }

            if (!Schema::hasColumn('room_date_discounts', 'admin_id')) {
                $table->foreignId('admin_id')->nullable()->after('discount_percent')
                    ->constrained('admins', 'admin_id')
                    ->nullOnDelete();
            }
        });

        DB::table('room_date_discounts')
            ->whereNull('discount_date')
            ->update(['discount_date' => DB::raw('discount_date_start')]);

        Schema::table('room_date_discounts', function (Blueprint $table): void {
            $table->date('discount_date')->nullable(false)->change();
            $table->dropColumn(['discount_date_start', 'discount_date_end']);
            $table->unique(['room_id', 'discount_date'], 'room_date_discounts_room_date_unique');
            $table->index('discount_date');
        });
    }

    private function dropForeignIfExists(Blueprint $table, string $constraintName): void
    {
        try {
            $table->dropForeign($constraintName);
        } catch (Throwable) {
            //
        }
    }

    private function dropIndexIfExists(Blueprint $table, string $indexName, string $type = 'index'): void
    {
        try {
            if ($type === 'unique') {
                $table->dropUnique($indexName);
                return;
            }

            $table->dropIndex($indexName);
        } catch (Throwable) {
            //
        }
    }
};

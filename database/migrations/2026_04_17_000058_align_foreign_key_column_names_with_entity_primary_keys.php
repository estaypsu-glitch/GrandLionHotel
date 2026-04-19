<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameForeignColumn(
            table: 'staff',
            from: 'created_by_admin_id',
            to: 'admin_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'staff_created_by_admin_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('admin_id')
                    ->references('admin_id')
                    ->on('admins')
                    ->nullOnDelete();
            }
        );

        $this->renameForeignColumn(
            table: 'rooms',
            from: 'status_updated_by_admin_id',
            to: 'admin_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'rooms_status_updated_by_admin_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('admin_id')
                    ->references('admin_id')
                    ->on('admins')
                    ->nullOnDelete();
            }
        );

        $this->renameForeignColumn(
            table: 'bookings',
            from: 'assigned_staff_id',
            to: 'staff_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'bookings_assigned_staff_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('staff_id')
                    ->references('staff_id')
                    ->on('staff')
                    ->nullOnDelete();
            }
        );

        $this->renameForeignColumn(
            table: 'booking_guest_details',
            from: 'created_by_staff_id',
            to: 'staff_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'booking_guest_details_created_by_staff_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('staff_id')
                    ->references('staff_id')
                    ->on('staff')
                    ->nullOnDelete();
            }
        );

        $this->renameForeignColumn(
            table: 'payments',
            from: 'verified_by_staff_id',
            to: 'staff_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'payments_verified_by_staff_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('staff_id')
                    ->references('staff_id')
                    ->on('staff')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        $this->renameForeignColumn(
            table: 'staff',
            from: 'admin_id',
            to: 'created_by_admin_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'staff_admin_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('created_by_admin_id')
                    ->references('admin_id')
                    ->on('admins')
                    ->nullOnDelete();
            }
        );

        $this->renameForeignColumn(
            table: 'rooms',
            from: 'admin_id',
            to: 'status_updated_by_admin_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'rooms_admin_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('status_updated_by_admin_id')
                    ->references('admin_id')
                    ->on('admins')
                    ->nullOnDelete();
            }
        );

        $this->renameForeignColumn(
            table: 'bookings',
            from: 'staff_id',
            to: 'assigned_staff_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'bookings_staff_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('assigned_staff_id')
                    ->references('staff_id')
                    ->on('staff')
                    ->nullOnDelete();
            }
        );

        $this->renameForeignColumn(
            table: 'booking_guest_details',
            from: 'staff_id',
            to: 'created_by_staff_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'booking_guest_details_staff_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('created_by_staff_id')
                    ->references('staff_id')
                    ->on('staff')
                    ->nullOnDelete();
            }
        );

        $this->renameForeignColumn(
            table: 'payments',
            from: 'staff_id',
            to: 'verified_by_staff_id',
            definition: 'BIGINT UNSIGNED NULL',
            oldForeignKey: 'payments_staff_id_foreign',
            newForeignKeyCallback: static function (Blueprint $table): void {
                $table->foreign('verified_by_staff_id')
                    ->references('staff_id')
                    ->on('staff')
                    ->nullOnDelete();
            }
        );
    }

    private function renameForeignColumn(
        string $table,
        string $from,
        string $to,
        string $definition,
        string $oldForeignKey,
        callable $newForeignKeyCallback
    ): void {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, $to) || !Schema::hasColumn($table, $from)) {
            return;
        }

        $this->dropForeignIfExists($table, $oldForeignKey);

        Schema::table($table, static function (Blueprint $table) use ($from, $to): void {
            $table->renameColumn($from, $to);
        });

        Schema::table($table, static function (Blueprint $table) use ($newForeignKeyCallback): void {
            $newForeignKeyCallback($table);
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
            // Ignore if the key is already absent.
        }
    }
};

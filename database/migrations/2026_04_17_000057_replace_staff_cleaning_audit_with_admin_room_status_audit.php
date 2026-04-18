<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('rooms', 'rooms_last_cleaned_by_staff_id_foreign');

        Schema::table('rooms', function (Blueprint $table): void {
            if (!Schema::hasColumn('rooms', 'status_updated_by_admin_id')) {
                $table->unsignedBigInteger('status_updated_by_admin_id')->nullable()->after('room_status_id');
            }

            if (!Schema::hasColumn('rooms', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status_updated_by_admin_id');
            }
        });

        if (Schema::hasColumn('rooms', 'last_cleaned_by_staff_id') || Schema::hasColumn('rooms', 'last_cleaned_at')) {
            Schema::table('rooms', function (Blueprint $table): void {
                if (Schema::hasColumn('rooms', 'last_cleaned_by_staff_id')) {
                    $table->dropColumn('last_cleaned_by_staff_id');
                }

                if (Schema::hasColumn('rooms', 'last_cleaned_at')) {
                    $table->dropColumn('last_cleaned_at');
                }
            });
        }

        if (
            Schema::hasTable('admins')
            && Schema::hasColumn('admins', 'admin_id')
            && Schema::hasColumn('rooms', 'status_updated_by_admin_id')
        ) {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->foreign('status_updated_by_admin_id')
                    ->references('admin_id')
                    ->on('admins')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $this->dropForeignIfExists('rooms', 'rooms_status_updated_by_admin_id_foreign');

        Schema::table('rooms', function (Blueprint $table): void {
            if (!Schema::hasColumn('rooms', 'last_cleaned_by_staff_id')) {
                $table->unsignedBigInteger('last_cleaned_by_staff_id')->nullable()->after('room_status_id');
            }

            if (!Schema::hasColumn('rooms', 'last_cleaned_at')) {
                $table->timestamp('last_cleaned_at')->nullable()->after('last_cleaned_by_staff_id');
            }
        });

        if (Schema::hasColumn('rooms', 'status_updated_by_admin_id') || Schema::hasColumn('rooms', 'status_updated_at')) {
            Schema::table('rooms', function (Blueprint $table): void {
                if (Schema::hasColumn('rooms', 'status_updated_by_admin_id')) {
                    $table->dropColumn('status_updated_by_admin_id');
                }

                if (Schema::hasColumn('rooms', 'status_updated_at')) {
                    $table->dropColumn('status_updated_at');
                }
            });
        }

        if (
            Schema::hasTable('staff')
            && Schema::hasColumn('staff', 'staff_id')
            && Schema::hasColumn('rooms', 'last_cleaned_by_staff_id')
        ) {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->foreign('last_cleaned_by_staff_id')
                    ->references('staff_id')
                    ->on('staff')
                    ->nullOnDelete();
            });
        }
    }

    private function dropForeignIfExists(string $table, string $constraintName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $databaseName = DB::getDatabaseName();

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraintName));
        }
    }
};

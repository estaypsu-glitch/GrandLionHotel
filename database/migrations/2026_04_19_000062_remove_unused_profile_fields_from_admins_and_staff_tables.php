<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['admins', 'staff'] as $tableName) {
            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn($tableName, 'address_line') ? 'address_line' : null,
                Schema::hasColumn($tableName, 'city') ? 'city' : null,
                Schema::hasColumn($tableName, 'province') ? 'province' : null,
                Schema::hasColumn($tableName, 'country') ? 'country' : null,
                Schema::hasColumn($tableName, 'email_verified_at') ? 'email_verified_at' : null,
            ]));

            if ($columnsToDrop === []) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        foreach (['admins', 'staff'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (!Schema::hasColumn($tableName, 'address_line')) {
                    $table->string('address_line')->nullable()->after('phone');
                }
                if (!Schema::hasColumn($tableName, 'city')) {
                    $table->string('city', 120)->nullable()->after('address_line');
                }
                if (!Schema::hasColumn($tableName, 'province')) {
                    $table->string('province', 120)->nullable()->after('city');
                }
                if (!Schema::hasColumn($tableName, 'country')) {
                    $table->string('country', 120)->nullable()->after('province');
                }
                if (!Schema::hasColumn($tableName, 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable()->after('country');
                }
            });
        }
    }
};

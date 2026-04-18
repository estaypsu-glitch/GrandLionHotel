<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['admins', 'staff'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'google_id')) {
                continue;
            }

            try {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->dropUnique($tableName.'_google_id_unique');
                });
            } catch (\Throwable) {
                // Ignore missing indexes so the migration stays safe on existing databases.
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('google_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['admins', 'staff'] as $tableName) {
            if (Schema::hasColumn($tableName, 'google_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('google_id')->nullable()->after('email');
            });

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->unique('google_id', $tableName.'_google_id_unique');
            });
        }
    }
};

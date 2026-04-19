<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'salary_type')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex(['role', 'salary_type']);
                $table->dropColumn('salary_type');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'salary_type')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('salary_type', 20)->default('daily')->after('role');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('salary_type', 20)
                ->default('daily')
                ->after('role_id');
            $table->decimal('salary_rate', 10, 2)
                ->default(0)
                ->after('salary_type');

            $table->index(['role', 'salary_type']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'salary_type']);
            $table->dropColumn(['salary_type', 'salary_rate']);
        });
    }
};

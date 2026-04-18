<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('rooms', 'view_type')) {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->string('view_type', 100)->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rooms', 'view_type')) {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->dropColumn('view_type');
            });
        }
    }
};

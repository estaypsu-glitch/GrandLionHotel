<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('cleaning_status_id')
                ->nullable()
                ->after('room_type_id')
                ->constrained('room_cleaning_statuses')
                ->nullOnDelete();
                
            $table->foreignId('last_cleaned_by')
                ->nullable()
                ->after('cleaning_status_id')
                ->constrained('users')
                ->nullOnDelete();
                
            $table->timestamp('last_cleaned_at')->nullable()->after('last_cleaned_by');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cleaning_status_id');
            $table->dropConstrainedForeignId('last_cleaned_by');
            $table->dropColumn('last_cleaned_at');
        });
    }
};


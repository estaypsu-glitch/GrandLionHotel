<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_verifications', function (Blueprint $table) {
            // Add foreign key to users table via email
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
            
            // Drop the email column since we're now using user_id
            // But first let's keep it for backward compatibility, just add unique constraint
            $table->dropUnique(['email']);
        });
    }

    public function down(): void
    {
        Schema::table('registration_verifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->unique('email');
        });
    }
};


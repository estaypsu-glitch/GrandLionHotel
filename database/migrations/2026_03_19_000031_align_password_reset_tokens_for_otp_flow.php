<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('password_reset_tokens')) {
            return;
        }

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('password_reset_tokens', 'code_hash')) {
                $table->string('code_hash')->nullable();
            }

            if (!Schema::hasColumn('password_reset_tokens', 'code_expires_at')) {
                $table->timestamp('code_expires_at')->nullable();
            }

            if (!Schema::hasColumn('password_reset_tokens', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0);
            }

            if (!Schema::hasColumn('password_reset_tokens', 'last_sent_at')) {
                $table->timestamp('last_sent_at')->nullable();
            }

            if (!Schema::hasColumn('password_reset_tokens', 'otp_channel')) {
                $table->string('otp_channel', 16)->default('email');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('password_reset_tokens')) {
            return;
        }

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('password_reset_tokens', 'otp_channel')) {
                $table->dropColumn('otp_channel');
            }

            if (Schema::hasColumn('password_reset_tokens', 'last_sent_at')) {
                $table->dropColumn('last_sent_at');
            }

            if (Schema::hasColumn('password_reset_tokens', 'attempts')) {
                $table->dropColumn('attempts');
            }

            if (Schema::hasColumn('password_reset_tokens', 'code_expires_at')) {
                $table->dropColumn('code_expires_at');
            }

            if (Schema::hasColumn('password_reset_tokens', 'code_hash')) {
                $table->dropColumn('code_hash');
            }
        });
    }
};


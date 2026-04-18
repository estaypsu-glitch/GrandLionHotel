<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where(function ($query): void {
                $query->whereNull('role')->orWhere('role', '');
            })
            ->update(['role' => 'customer']);

        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'payment_method_id')) {
                $table->dropConstrainedForeignId('payment_method_id');
            }
            if (Schema::hasColumn('payments', 'payment_status_id')) {
                $table->dropConstrainedForeignId('payment_status_id');
            }
        });

        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'booking_status_id')) {
                $table->dropConstrainedForeignId('booking_status_id');
            }
            if (Schema::hasColumn('bookings', 'payment_status_id')) {
                $table->dropConstrainedForeignId('payment_status_id');
            }
        });

        Schema::table('rooms', function (Blueprint $table): void {
            if (Schema::hasColumn('rooms', 'room_type_id')) {
                $table->dropConstrainedForeignId('room_type_id');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropConstrainedForeignId('role_id');
            }
            if (Schema::hasColumn('users', 'is_admin')) {
                $table->dropColumn('is_admin');
            }
        });

        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('payment_statuses');
        Schema::dropIfExists('booking_statuses');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('room_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('payment_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->foreignId('role_id')
                ->nullable()
                ->after('role')
                ->constrained('roles')
                ->nullOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->foreignId('room_type_id')
                ->nullable()
                ->after('id')
                ->constrained('room_types')
                ->nullOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('booking_status_id')
                ->nullable()
                ->after('room_id')
                ->constrained('booking_statuses')
                ->nullOnDelete();

            $table->foreignId('payment_status_id')
                ->nullable()
                ->after('booking_status_id')
                ->constrained('payment_statuses')
                ->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('booking_id')
                ->constrained('payment_methods')
                ->nullOnDelete();

            $table->foreignId('payment_status_id')
                ->nullable()
                ->after('payment_method_id')
                ->constrained('payment_statuses')
                ->nullOnDelete();
        });
    }
};


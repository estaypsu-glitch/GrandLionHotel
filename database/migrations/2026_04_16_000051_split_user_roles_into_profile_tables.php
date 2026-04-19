<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        $timestamp = now();

        $adminUserIds = DB::table('users')
            ->where('role', 'admin')
            ->pluck('id');

        foreach ($adminUserIds as $userId) {
            DB::table('admins')->updateOrInsert(
                ['user_id' => $userId],
                ['created_at' => $timestamp, 'updated_at' => $timestamp]
            );
        }

        $firstAdminId = DB::table('admins')->orderBy('id')->value('id');

        $staffUserIds = DB::table('users')
            ->where('role', 'staff')
            ->pluck('id');

        foreach ($staffUserIds as $userId) {
            DB::table('staff')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'created_by_admin_id' => $firstAdminId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]
            );
        }

        $customerUserIds = DB::table('users')
            ->where(function ($query): void {
                $query->where('role', 'customer')
                    ->orWhereNull('role')
                    ->orWhere('role', '');
            })
            ->pluck('id');

        foreach ($customerUserIds as $userId) {
            DB::table('customers')->updateOrInsert(
                ['user_id' => $userId],
                ['created_at' => $timestamp, 'updated_at' => $timestamp]
            );
        }

        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        }

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['role']);
                $table->dropColumn('role');
            });
        }

        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $timestamp = now();

        DB::table('roles')->insert([
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full administrative access.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'Front desk and booking operations access.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Customer',
                'slug' => 'customer',
                'description' => 'Guest account access.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);

        $roleMap = DB::table('roles')->pluck('id', 'slug');

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->after('country')->index();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->nullable()
                ->after('role')
                ->constrained('roles')
                ->nullOnDelete();
        });

        DB::table('users')->update([
            'role' => 'customer',
            'role_id' => $roleMap['customer'] ?? null,
        ]);

        $adminUserIds = DB::table('admins')->pluck('user_id');
        if ($adminUserIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $adminUserIds)
                ->update([
                    'role' => 'admin',
                    'role_id' => $roleMap['admin'] ?? null,
                ]);
        }

        $staffUserIds = DB::table('staff')->pluck('user_id');
        if ($staffUserIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $staffUserIds)
                ->update([
                    'role' => 'staff',
                    'role_id' => $roleMap['staff'] ?? null,
                ]);
        }

        Schema::dropIfExists('customers');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('admins');
    }
};

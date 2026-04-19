<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rooms') || !Schema::hasColumn('rooms', 'name')) {
            return;
        }

        DB::table('rooms')
            ->where('name', 'Room 101 - Garden Standard')
            ->update(['name' => 'Room 101']);

        DB::table('rooms')
            ->where('name', 'Room 102 - City Standard')
            ->update(['name' => 'Room 102']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('rooms') || !Schema::hasColumn('rooms', 'name')) {
            return;
        }

        DB::table('rooms')
            ->where('name', 'Room 101')
            ->update(['name' => 'Room 101 - Garden Standard']);

        DB::table('rooms')
            ->where('name', 'Room 102')
            ->update(['name' => 'Room 102 - City Standard']);
    }
};


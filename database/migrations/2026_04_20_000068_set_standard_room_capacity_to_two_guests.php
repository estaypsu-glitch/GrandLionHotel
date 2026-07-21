<?php

use App\Models\Room;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rooms')->update([
            'capacity' => Room::standardGuestCapacity(),
        ]);
    }

    public function down(): void
    {
        // Historical room capacities cannot be restored automatically.
    }
};

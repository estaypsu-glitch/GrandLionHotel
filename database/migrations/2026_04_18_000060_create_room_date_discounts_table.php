<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_date_discounts', function (Blueprint $table): void {
            $table->bigIncrements('room_date_discount_id');
            $table->foreignId('room_id')
                ->constrained('rooms', 'room_id')
                ->cascadeOnDelete();
            $table->date('discount_date');
            $table->decimal('discount_percent', 5, 2);
            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('admins', 'admin_id')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['room_id', 'discount_date'], 'room_date_discounts_room_date_unique');
            $table->index('discount_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_date_discounts');
    }
};


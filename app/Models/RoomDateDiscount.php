<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomDateDiscount extends Model
{
    use HasLegacyIdAttribute;

    protected $primaryKey = 'room_date_discount_id';

    protected $fillable = [
        'room_id',
        'discount_date',
        'discount_percent',
        'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'discount_date' => 'date',
            'discount_percent' => 'decimal:2',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }
}


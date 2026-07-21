<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Model;

class RoomDateDiscount extends Model
{
    use HasLegacyIdAttribute;

    protected $primaryKey = 'room_date_discount_id';

    protected $fillable = [
        'room_id',
        'discount_date_start',
        'discount_date_end',
        'discount_percent',
    ];

    protected function casts(): array
    {
        return [
            'discount_date_start' => 'date',
            'discount_date_end' => 'date',
            'discount_percent' => 'decimal:2',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

}

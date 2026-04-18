<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDiscount extends Model
{
    use HasLegacyIdAttribute;

    protected $primaryKey = 'booking_discount_id';

    protected $fillable = [
        'booking_id',
        'discount_type',
        'discount_id',
        'discount_id_photo_path',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}

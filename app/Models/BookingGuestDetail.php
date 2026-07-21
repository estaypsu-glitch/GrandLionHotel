<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingGuestDetail extends Model
{
    use HasFactory;
    use HasLegacyIdAttribute;

    protected $primaryKey = 'guest_detail_id';

    protected $fillable = [
        'booking_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address_line',
        'street_address_line_2',
        'city',
        'province',
        'postal_code',
        'adults',
        'kids',
        'payment_preference',
    ];

    protected function casts(): array
    {
        return [
            'adults' => 'integer',
            'kids' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

}

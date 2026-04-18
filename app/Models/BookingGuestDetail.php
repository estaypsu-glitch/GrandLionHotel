<?php

namespace App\Models;

use App\Models\Staff;
use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'staff_id',
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

    public function createdByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }
}

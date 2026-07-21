<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class RefundRequest extends Model
{
    use HasFactory;
    use HasLegacyIdAttribute;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_REJECTED = 'rejected';

    protected $primaryKey = 'refund_request_id';

    protected $fillable = [
        'payment_id',
        'reason',
        'status',
        'notes',
        'requested_at',
        'approved_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function booking(): HasOneThrough
    {
        return $this->hasOneThrough(
            Booking::class,
            Payment::class,
            'payment_id',
            'booking_id',
            'payment_id',
            'booking_id'
        );
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

}

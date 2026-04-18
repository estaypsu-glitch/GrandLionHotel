<?php

namespace App\Models;

use App\Models\Staff;
use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;
    use HasLegacyIdAttribute;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'booking_id',
        'amount',
        'method',
        'status',
        'source',
        'qr_reference',
        'customer_reference',
        'payment_proof_path',
        'original_amount',
        'discount_rate',
        'discount_amount',
        'transaction_reference',
        'paid_at',
        'verified_at',
        'staff_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'original_amount' => 'decimal:2',
            'discount_rate' => 'decimal:4',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function verifiedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    public function ensureTransactionReference(?int $bookingId = null): string
    {
        $existingReference = strtoupper(trim((string) $this->transaction_reference));
        if ($existingReference !== '') {
            if ($existingReference !== $this->transaction_reference) {
                $this->forceFill(['transaction_reference' => $existingReference])->save();
            }

            return $existingReference;
        }

        $resolvedBookingId = max(1, (int) ($bookingId ?? $this->booking_id));

        do {
            $candidate = self::generateTransactionReference($resolvedBookingId);

            $query = self::query()->where('transaction_reference', $candidate);
            if ($this->exists) {
                $query->where($this->getKeyName(), '!=', $this->getKey());
            }
        } while ($query->exists());

        $this->forceFill(['transaction_reference' => $candidate])->save();

        return $candidate;
    }

    public static function generateTransactionReference(int $bookingId): string
    {
        $datePart = now()->format('Ymd');
        $bookingPart = str_pad((string) max(1, $bookingId), 6, '0', STR_PAD_LEFT);
        $randomPart = Str::upper(Str::random(6));

        return "GLH-{$datePart}-B{$bookingPart}-{$randomPart}";
    }

}

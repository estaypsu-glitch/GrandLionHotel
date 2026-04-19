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

    public const METHOD_CASH = 'cash';
    public const METHOD_INSTAPAY = 'instapay';
    public const METHOD_CREDIT_DEBIT_CARD = 'credit_debit_card';

    public const ONLINE_METHODS = [
        self::METHOD_INSTAPAY,
        self::METHOD_CREDIT_DEBIT_CARD,
    ];

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

    public static function allowedMethods(): array
    {
        return [
            self::METHOD_CASH,
            self::METHOD_INSTAPAY,
            self::METHOD_CREDIT_DEBIT_CARD,
        ];
    }

    public static function isOnlineMethod(?string $method): bool
    {
        return in_array(strtolower(trim((string) $method)), self::ONLINE_METHODS, true);
    }

    public static function methodLabel(?string $method): string
    {
        $normalized = strtolower(trim((string) $method));

        return match ($normalized) {
            self::METHOD_CASH => 'Cash',
            self::METHOD_INSTAPAY, 'bank_transfer', 'gcash', 'paymaya' => 'InstaPay',
            self::METHOD_CREDIT_DEBIT_CARD => 'Credit/Debit Card',
            default => ucfirst(str_replace('_', ' ', $normalized !== '' ? $normalized : 'n/a')),
        };
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

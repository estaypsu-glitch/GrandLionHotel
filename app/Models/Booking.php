<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Booking extends Model
{
    use HasFactory;
    use HasLegacyIdAttribute;

    protected static ?bool $bookingDiscountsTableExists = null;

    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'customer_id',
        'room_id',
        'check_in',
        'check_out',
        'requested_check_in',
        'requested_check_out',
        'reschedule_request_notes',
        'reschedule_requested_at',
        'status',
        'notes',
        'actual_check_in_at',
        'actual_check_out_at',
        'staff_id',
        'staff_notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'requested_check_in' => 'date',
            'requested_check_out' => 'date',
            'reschedule_requested_at' => 'datetime',
            'actual_check_in_at' => 'datetime',
            'actual_check_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'booking_id', 'booking_id');
    }

    public function guestDetail(): HasOne
    {
        return $this->hasOne(BookingGuestDetail::class, 'booking_id', 'booking_id');
    }

    public function discount(): HasOne
    {
        return $this->hasOne(BookingDiscount::class, 'booking_id', 'booking_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    public function nights(): int
    {
        $start = Carbon::parse($this->check_in)->startOfDay();
        $end = Carbon::parse($this->check_out)->startOfDay();

        return max(1, $start->diffInDays($end));
    }

    public function billedUnits(): int
    {
        return $this->nights();
    }

    public function getGuestsAttribute(mixed $value = null): int
    {
        $detail = $this->guestDetailRecord();

        if ($detail) {
            $adults = max(0, (int) ($detail->adults ?? 0));
            $kids = max(0, (int) ($detail->kids ?? 0));

            return max(1, $adults + $kids);
        }

        if (is_numeric($value)) {
            return max(1, (int) $value);
        }

        return 1;
    }

    public function getTotalPriceAttribute(mixed $value = null): float
    {
        $payment = $this->getRelationValue('payment');
        if ($payment) {
            return round((float) $payment->amount, 2);
        }

        $paymentAmount = $this->payment()->value('amount');
        if (!is_null($paymentAmount)) {
            return round((float) $paymentAmount, 2);
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        return $this->calculateExpectedTotalPrice();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true)
            && Carbon::parse($this->check_in)->startOfDay()->isFuture();
    }

    public function hasPendingRescheduleRequest(): bool
    {
        return !is_null($this->requested_check_in)
            && !is_null($this->requested_check_out)
            && !is_null($this->reschedule_requested_at);
    }

    public function canRequestReschedule(): bool
    {
        return $this->status === 'confirmed'
            && $this->payment_status === 'unpaid'
            && is_null($this->actual_check_in_at)
            && is_null($this->actual_check_out_at)
            && $this->check_in?->copy()->startOfDay()->greaterThanOrEqualTo(now()->startOfDay());
    }

    public function canBeRescheduledByStaff(): bool
    {
        return $this->status === 'confirmed'
            && is_null($this->actual_check_in_at)
            && is_null($this->actual_check_out_at)
            && $this->check_out?->copy()->startOfDay()->greaterThanOrEqualTo(now()->startOfDay());
    }

    public function scopeWherePaymentStatus(Builder $query, string $paymentStatus): Builder
    {
        $status = trim(strtolower($paymentStatus));

        if ($status === '') {
            return $query;
        }

        if ($status === 'unpaid') {
            return $query->where(function (Builder $nested): void {
                $nested->whereDoesntHave('payment')
                    ->orWhereHas('payment', static fn (Builder $paymentQuery) => $paymentQuery->whereIn('status', ['unpaid', 'pending_verification']));
            });
        }

        return $query->whereHas('payment', static fn (Builder $paymentQuery) => $paymentQuery->where('status', $status));
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            'cancelled' => [],
            'completed' => [],
        ];

        return in_array($targetStatus, $allowedTransitions[$this->status] ?? [], true);
    }

    public function canBeConfirmedByStaff(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeCheckedInByStaff(): bool
    {
        return $this->status === 'confirmed'
            && $this->check_in?->copy()->startOfDay()->lessThanOrEqualTo(now()->startOfDay())
            && is_null($this->actual_check_in_at)
            && is_null($this->actual_check_out_at);
    }

    public function canBeCheckedOutByStaff(): bool
    {
        return $this->status === 'confirmed'
            && !is_null($this->actual_check_in_at)
            && is_null($this->actual_check_out_at);
    }

    public function canBeTransferredByStaff(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true)
            && is_null($this->actual_check_out_at);
    }

    public function isInHouse(): bool
    {
        return !is_null($this->actual_check_in_at)
            && is_null($this->actual_check_out_at)
            && $this->status === 'confirmed';
    }

    public function guestName(): string
    {
        if (filled($this->user?->name)) {
            return (string) $this->user->name;
        }

        $detail = $this->guestDetailRecord();
        $profileName = trim(trim((string) ($detail?->first_name ?? '')).' '.trim((string) ($detail?->last_name ?? '')));

        return $profileName !== '' ? $profileName : 'Guest';
    }

    public function guestEmail(): string
    {
        if (filled($this->user?->email)) {
            return (string) $this->user->email;
        }

        $detail = $this->guestDetailRecord();
        $value = trim((string) ($detail?->email ?? ''));
        if ($value !== '') {
            return $value;
        }

        return '-';
    }

    public function guestPhone(): string
    {
        if (filled($this->user?->phone)) {
            return (string) $this->user->phone;
        }

        $detail = $this->guestDetailRecord();
        $value = trim((string) ($detail?->phone ?? ''));
        if ($value !== '') {
            return $value;
        }

        return '-';
    }

    public function getPaymentStatusAttribute(): string
    {
        $payment = $this->getRelationValue('payment');

        $status = strtolower(trim((string) ($payment?->status ?? '')));

        return $status !== '' ? $status : 'unpaid';
    }

    public function getReservationMetaAttribute(): array
    {
        $detail = $this->guestDetailRecord();
        if (!$detail) {
            return [];
        }

        $discount = $this->discountRecord();

        $fullName = trim(trim((string) $detail->first_name).' '.trim((string) $detail->last_name));

        return array_filter([
            'customer_name' => $fullName !== '' ? $fullName : null,
            'customer_email' => $detail->email,
            'customer_phone' => $detail->phone,
            'first_name' => $detail->first_name,
            'last_name' => $detail->last_name,
            'street_address' => $detail->address_line,
            'street_address_line_2' => $detail->street_address_line_2,
            'guest_city' => $detail->city,
            'state_province' => $detail->province,
            'postal_code' => $detail->postal_code,
            'contact_phone' => $detail->phone,
            'contact_email' => $detail->email,
            'email' => $detail->email,
            'phone' => $detail->phone,
            'address_line' => $detail->address_line,
            'city' => $detail->city,
            'province' => $detail->province,
            'adults' => $detail->adults,
            'kids' => $detail->kids,
            'payment_preference' => $detail->payment_preference,
            'discount_type' => $discount?->discount_type,
            'discount_id' => $discount?->discount_id,
            'discount_id_photo_path' => $discount?->discount_id_photo_path,
            'staff_id' => $detail->staff_id,
        ], static fn ($value): bool => !is_null($value) && $value !== '');
    }

    private function guestDetailRecord(): ?BookingGuestDetail
    {
        $detail = $this->getRelationValue('guestDetail');
        if ($detail) {
            return $detail;
        }

        if ($this->relationLoaded('guestDetail') || is_null($this->id)) {
            return null;
        }

        $detail = $this->guestDetail()->first();
        $this->setRelation('guestDetail', $detail);

        return $detail;
    }

    private function discountRecord(): ?BookingDiscount
    {
        $discount = $this->getRelationValue('discount');
        if ($discount) {
            return $discount;
        }

        if (!$this->supportsBookingDiscountTable()) {
            return $this->legacyDiscountRecord();
        }

        if ($this->relationLoaded('discount') || is_null($this->id)) {
            return null;
        }

        $discount = $this->discount()->first();
        $this->setRelation('discount', $discount);

        return $discount;
    }

    private function legacyDiscountRecord(): ?BookingDiscount
    {
        $detail = $this->guestDetailRecord();
        $payment = $this->getRelationValue('payment');

        if (!$payment && !is_null($this->id)) {
            $payment = $this->payment()->first();
            if ($payment) {
                $this->setRelation('payment', $payment);
            }
        }

        $discountType = $this->firstFilledText($detail?->discount_type, $payment?->discount_type);
        $discountId = $this->firstFilledText($detail?->discount_id, $payment?->discount_id);
        $discountProofPath = $this->firstFilledText($detail?->discount_id_photo_path, $payment?->discount_id_photo_path);

        if ($discountType === null && $discountId === null && $discountProofPath === null) {
            return null;
        }

        return new BookingDiscount([
            'booking_id' => $this->id,
            'discount_type' => $discountType,
            'discount_id' => $discountId,
            'discount_id_photo_path' => $discountProofPath,
        ]);
    }

    private function firstFilledText(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    private function supportsBookingDiscountTable(): bool
    {
        if (is_null(self::$bookingDiscountsTableExists)) {
            self::$bookingDiscountsTableExists = Schema::hasTable('booking_discounts');
        }

        return self::$bookingDiscountsTableExists;
    }

    private function calculateExpectedTotalPrice(): float
    {
        $room = $this->getRelationValue('room');
        if (!$room && !is_null($this->room_id)) {
            $room = $this->room()->first(['room_id', 'price_per_night']);
            if ($room) {
                $this->setRelation('room', $room);
            }
        }

        if (!$room) {
            return 0.0;
        }

        return round((float) $room->price_per_night * $this->billedUnits(), 2);
    }
}

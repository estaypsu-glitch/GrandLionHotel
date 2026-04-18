<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Room extends Model
{
    use HasFactory;
    use HasLegacyIdAttribute;

    public const BOOKABLE_ROOM_STATUS_SLUGS = ['clean', 'inspected'];

    private const FALLBACK_IMAGES = [
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1522798514-97ceb8c4f1c8?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1455587734955-081b22074882?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1400&q=80',
    ];

    protected $primaryKey = 'room_id';

    protected $fillable = [
        'name',
        'type',
        'view_type',
        'description',
        'price_per_night',
        'capacity',
        'image',
        'room_status_id',
        'admin_id',
        'status_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'price_per_night' => 'decimal:2',
            'status_updated_at' => 'datetime',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'room_id', 'room_id');
    }

    public function dateDiscounts(): HasMany
    {
        return $this->hasMany(RoomDateDiscount::class, 'room_id', 'room_id');
    }

    public function roomStatus(): BelongsTo
    {
        return $this->belongsTo(RoomStatus::class, 'room_status_id', 'room_status_id');
    }

    public function statusUpdatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    public function getImageUrlAttribute(): string
    {
        if (!empty($this->image)) {
            return $this->image;
        }

        $seed = $this->id ?? crc32((string) $this->name);
        $index = abs((int) $seed) % count(self::FALLBACK_IMAGES);

        return self::FALLBACK_IMAGES[$index];
    }

    public function getPricePerHourAttribute(): float
    {
        return round((float) $this->price_per_night / 12, 2);
    }

    public function getIsAvailableAttribute(): bool
    {
        $roomStatusSlug = null;

        if ($this->relationLoaded('roomStatus')) {
            $roomStatusSlug = $this->roomStatus?->slug;
        } elseif (!is_null($this->room_status_id)) {
            $roomStatusSlug = $this->roomStatus()->value('slug');
        }

        return self::isBookableForCustomerByRoomStatus($roomStatusSlug);
    }

    public function scopeAvailableForBooking(Builder $query): Builder
    {
        return $query->whereHas('roomStatus', function (Builder $roomStatusQuery): void {
            $roomStatusQuery->whereIn('slug', self::BOOKABLE_ROOM_STATUS_SLUGS);
        });
    }

    public function scopeUnavailableForBooking(Builder $query): Builder
    {
        return $query->where(function (Builder $nested): void {
            $nested->whereNull('room_status_id')
                ->orWhereHas('roomStatus', function (Builder $roomStatusQuery): void {
                    $roomStatusQuery->whereNotIn('slug', self::BOOKABLE_ROOM_STATUS_SLUGS);
                });
        });
    }

    public function scopeOrderByAvailability(Builder $query, string $direction = 'desc'): Builder
    {
        $normalizedDirection = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $placeholders = implode(',', array_fill(0, count(self::BOOKABLE_ROOM_STATUS_SLUGS), '?'));

        return $query->orderByRaw(
            "CASE WHEN EXISTS (
                SELECT 1
                FROM room_status rs
                WHERE rs.room_status_id = rooms.room_status_id
                AND rs.slug IN ({$placeholders})
            ) THEN 1 ELSE 0 END {$normalizedDirection}",
            self::BOOKABLE_ROOM_STATUS_SLUGS
        );
    }

    public static function isBookableForCustomerByRoomStatus(?string $roomStatusSlug): bool
    {
        $slug = strtolower(trim((string) $roomStatusSlug));

        if ($slug === '') {
            return false;
        }

        return in_array($slug, self::BOOKABLE_ROOM_STATUS_SLUGS, true);
    }
}

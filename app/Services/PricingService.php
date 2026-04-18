<?php

namespace App\Services;

use App\Models\RoomDateDiscount;
use App\Models\Room;
use Carbon\Carbon;
use InvalidArgumentException;

class PricingService
{
    public function calculateTotal(
        Room $room,
        string $checkIn,
        string $checkOut
    ): float
    {
        $start = Carbon::parse($checkIn)->startOfDay();
        $end = Carbon::parse($checkOut)->startOfDay();
        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Check-out must be at least one day after check-in.');
        }

        $discountsByDate = RoomDateDiscount::query()
            ->where('room_id', (int) $room->id)
            ->whereBetween('discount_date', [$start->toDateString(), $end->copy()->subDay()->toDateString()])
            ->pluck('discount_percent', 'discount_date');

        $nightlyRate = (float) $room->price_per_night;
        $total = 0.0;
        $cursor = $start->copy();

        while ($cursor->lt($end)) {
            $discountPercent = (float) ($discountsByDate[$cursor->toDateString()] ?? 0);
            $discountPercent = max(0, min(100, $discountPercent));
            $multiplier = 1 - ($discountPercent / 100);

            $total += $nightlyRate * $multiplier;
            $cursor->addDay();
        }

        return round($total, 2);
    }
}

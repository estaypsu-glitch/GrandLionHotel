<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RoomDateDiscount;
use App\Models\Room;
use Carbon\Carbon;
use InvalidArgumentException;

class PricingService
{
    public function quoteStay(
        Room $room,
        string $checkIn,
        string $checkOut,
        ?int $guests = null
    ): array
    {
        $start = Carbon::parse($checkIn)->startOfDay();
        $end = Carbon::parse($checkOut)->startOfDay();
        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Check-out must be at least one day after check-in.');
        }

        $discountRanges = RoomDateDiscount::query()
            ->where('room_id', (int) $room->id)
            ->whereDate('discount_date_start', '<=', $end->copy()->subDay()->toDateString())
            ->whereDate('discount_date_end', '>=', $start->toDateString())
            ->get(['discount_date_start', 'discount_date_end', 'discount_percent']);

        $nightlyRate = round((float) $room->price_per_night, 2);
        $nights = $start->diffInDays($end);
        $guestCount = max(1, (int) ($guests ?? Room::standardGuestCapacity()));
        $extraBeddingCount = $this->calculateExtraBeddingCount($guestCount);
        $extraBeddingFeePerNight = $this->extraBeddingFeePerNight();
        $baseTotal = round($nightlyRate * $nights, 2);
        $total = 0.0;
        $discountAmount = 0.0;
        $discountedNights = 0;
        $breakdown = [];
        $cursor = $start->copy();

        while ($cursor->lt($end)) {
            $dateKey = $cursor->toDateString();
            $discountPercent = (float) ($discountRanges
                ->first(static fn (RoomDateDiscount $discount): bool => $cursor->betweenIncluded(
                    $discount->discount_date_start,
                    $discount->discount_date_end
                ))
                ?->discount_percent ?? 0);
            $discountPercent = max(0, min(100, $discountPercent));
            $multiplier = 1 - ($discountPercent / 100);
            $discountedRate = round($nightlyRate * $multiplier, 2);

            if ($discountPercent > 0) {
                $discountedNights++;
            }

            $total += $discountedRate;
            $discountAmount += max(0, $nightlyRate - $discountedRate);
            $breakdown[] = [
                'date' => $dateKey,
                'base_rate' => $nightlyRate,
                'discount_percent' => round($discountPercent, 2),
                'discounted_rate' => $discountedRate,
            ];

            $cursor->addDay();
        }

        $roomTotal = round($total, 2);
        $discountAmount = round($discountAmount, 2);
        $extraBeddingTotal = round($extraBeddingCount * $extraBeddingFeePerNight * $nights, 2);
        $grandTotal = round($roomTotal + $extraBeddingTotal, 2);

        return [
            'check_in' => $start->toDateString(),
            'check_out' => $end->toDateString(),
            'nights' => $nights,
            'guests' => $guestCount,
            'standard_guests' => Room::standardGuestCapacity(),
            'base_nightly_rate' => $nightlyRate,
            'average_nightly_rate' => $nights > 0 ? round($grandTotal / $nights, 2) : $nightlyRate,
            'base_total' => $baseTotal,
            'room_total' => $roomTotal,
            'extra_bedding_count' => $extraBeddingCount,
            'extra_bedding_fee_per_night' => $extraBeddingFeePerNight,
            'extra_bedding_total' => $extraBeddingTotal,
            'total' => $grandTotal,
            'discount_amount' => $discountAmount,
            'discounted_nights' => $discountedNights,
            'has_date_discount' => $discountedNights > 0,
            'nightly_breakdown' => $breakdown,
        ];
    }

    public function calculateTotal(
        Room $room,
        string $checkIn,
        string $checkOut,
        ?int $guests = null
    ): float
    {
        return $this->quoteStay($room, $checkIn, $checkOut, $guests)['total'];
    }

    public function quoteBooking(Booking $booking): array
    {
        $room = $booking->getRelationValue('room');

        if (!$room && !is_null($booking->room_id)) {
            $room = $booking->room()->first(['room_id', 'price_per_night']);
            if ($room) {
                $booking->setRelation('room', $room);
            }
        }

        if (!$room || !$booking->check_in || !$booking->check_out) {
            return [
                'check_in' => null,
                'check_out' => null,
                'nights' => 0,
                'guests' => max(1, (int) $booking->guests),
                'standard_guests' => Room::standardGuestCapacity(),
                'base_nightly_rate' => 0.0,
                'average_nightly_rate' => 0.0,
                'base_total' => 0.0,
                'room_total' => 0.0,
                'extra_bedding_count' => $this->calculateExtraBeddingCount($booking->guests),
                'extra_bedding_fee_per_night' => $this->extraBeddingFeePerNight(),
                'extra_bedding_total' => 0.0,
                'total' => 0.0,
                'discount_amount' => 0.0,
                'discounted_nights' => 0,
                'has_date_discount' => false,
                'nightly_breakdown' => [],
            ];
        }

        return $this->quoteStay(
            $room,
            $booking->check_in->toDateString(),
            $booking->check_out->toDateString(),
            $booking->guests
        );
    }

    public function calculateExtraBeddingCount(?int $guests): int
    {
        return max(0, max(1, (int) $guests) - Room::standardGuestCapacity());
    }

    public function extraBeddingFeePerNight(): float
    {
        return round(max(0, (float) config('pricing.extra_bedding_fee_per_night', 0)), 2);
    }
}

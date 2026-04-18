<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;

class AvailabilityService
{
    public function isRoomAvailable(Room $room, string $checkIn, string $checkOut, ?int $ignoreBookingId = null): bool
    {
        if (!$room->is_available) {
            return false;
        }

        $requestedRange = $this->resolveNightlyRange($checkIn, $checkOut);
        if (!$requestedRange) {
            return false;
        }

        [$requestedStart, $requestedEnd] = $requestedRange;

        $bookings = $room->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('check_in', '<=', $requestedEnd->toDateString())
            ->whereDate('check_out', '>=', $requestedStart->toDateString())
            ->when(!is_null($ignoreBookingId), static function ($query) use ($ignoreBookingId): void {
                $query->where('booking_id', '!=', $ignoreBookingId);
            })
            ->get(['booking_id', 'check_in', 'check_out']);

        foreach ($bookings as $booking) {
            $existingRange = $this->resolveExistingRange($booking);
            if (!$existingRange) {
                continue;
            }

            [$existingStart, $existingEnd] = $existingRange;

            if ($existingStart->lt($requestedEnd) && $existingEnd->gt($requestedStart)) {
                return false;
            }
        }

        return true;
    }

    private function resolveExistingRange(Booking $booking): ?array
    {
        return $this->resolveNightlyRange(
            $booking->check_in->toDateString(),
            $booking->check_out->toDateString()
        );
    }

    private function resolveNightlyRange(string $checkIn, string $checkOut): ?array
    {
        try {
            $start = Carbon::parse($checkIn)->startOfDay();
            $end = Carbon::parse($checkOut)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($end->lessThanOrEqualTo($start)) {
            return null;
        }

        return [$start, $end];
    }
}

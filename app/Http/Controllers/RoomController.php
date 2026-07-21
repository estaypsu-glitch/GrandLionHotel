<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class RoomController extends Controller
{
    public function __construct(
        private readonly PricingService $pricingService,
        private readonly AvailabilityService $availabilityService
    ) {
    }

    public function index(Request $request)
    {
        if ($normalizedStay = $this->normalizeStayDates($request)) {
            return redirect()->route('rooms.index', array_merge(
                $request->except(['check_in', 'check_out']),
                $normalizedStay
            ));
        }

        $stay = $this->resolveStayFilters($request);
        $roomsQuery = Room::query();

        $this->applyFilters($roomsQuery, $request, $stay);

        $this->applySort($roomsQuery, $request->string('sort', 'recommended')->toString());

        $rooms = $roomsQuery->paginate(9)->withQueryString();
        $this->attachStayPricing($rooms->getCollection(), $stay);

        return view('rooms.search', compact('rooms', 'stay'));
    }

    public function show(Request $request, Room $room)
    {
        if ($normalizedStay = $this->normalizeStayDates($request)) {
            return redirect()->route('rooms.show', array_merge(
                ['room' => $room],
                $request->except(['check_in', 'check_out']),
                $normalizedStay
            ));
        }

        $stay = $this->resolveStayFilters($request);
        $pricingPreview = $stay['is_valid']
            ? $this->pricingService->quoteStay($room, $stay['check_in'], $stay['check_out'])
            : null;
        $stayAvailability = $stay['is_valid']
            ? ($room->is_available && $this->availabilityService->isRoomAvailable($room, $stay['check_in'], $stay['check_out']))
            : $room->is_available;

        return view('rooms.show', compact('room', 'stay', 'pricingPreview', 'stayAvailability'));
    }

    public function search(Request $request)
    {
        if ($normalizedStay = $this->normalizeStayDates($request)) {
            return redirect()->route('rooms.search', array_merge(
                $request->except(['check_in', 'check_out']),
                $normalizedStay
            ));
        }

        $stay = $this->resolveStayFilters($request);
        $roomsQuery = Room::query();

        $this->applyFilters($roomsQuery, $request, $stay);

        $this->applySort($roomsQuery, $request->string('sort', 'recommended')->toString());

        $rooms = $roomsQuery->paginate(9)->withQueryString();
        $this->attachStayPricing($rooms->getCollection(), $stay);

        return view('rooms.search', compact('rooms', 'stay'));
    }

    public function pricingPreview(Request $request, Room $room): JsonResponse
    {
        if ($normalizedStay = $this->normalizeStayDates($request)) {
            $stay = $this->resolveStayFilters(new Request($normalizedStay));
        } else {
            $stay = $this->resolveStayFilters($request);
        }

        $requestedGuests = max(1, $request->integer('guests', Room::standardGuestCapacity()));

        if (!$stay['is_valid']) {
            return response()->json([
                'message' => 'Select a valid check-in and check-out date range.',
                'errors' => [
                    'check_out' => ['Check-out must be at least one day after check-in.'],
                ],
            ], 422);
        }

        $stayAvailable = $room->is_available
            && $this->availabilityService->isRoomAvailable($room, $stay['check_in'], $stay['check_out']);

        return response()->json([
            'pricing' => $this->pricingService->quoteStay($room, $stay['check_in'], $stay['check_out'], $requestedGuests),
            'availability' => [
                'room_status_available' => $room->is_available,
                'stay_available' => $stayAvailable,
                'message' => !$room->is_available
                    ? 'This room is currently unavailable.'
                    : ($stayAvailable
                        ? 'Available for your selected dates.'
                        : 'Unavailable for your selected dates.'),
            ],
        ]);
    }

    private function applySort(Builder $roomsQuery, string $sort): void
    {
        match ($sort) {
            'price_low' => $roomsQuery->orderBy('price_per_night'),
            'price_high' => $roomsQuery->orderByDesc('price_per_night'),
            'newest' => $roomsQuery->latest(),
            default => $roomsQuery->orderByAvailability('desc')->orderBy('price_per_night'),
        };
    }

    private function applyFilters(Builder $roomsQuery, Request $request, array $stay): void
    {
        $roomsQuery
            ->when(
                $request->filled('type'),
                function (Builder $query) use ($request): void {
                    $keyword = trim((string) $request->input('type'));

                    $query->where(function (Builder $nested) use ($keyword): void {
                        $nested->where('type', 'like', '%'.$keyword.'%')
                            ->orWhere('view_type', 'like', '%'.$keyword.'%');
                    });
                }
            )
            ->when(
                $request->filled('max_price') && is_numeric($request->input('max_price')),
                fn (Builder $query) => $query->where('price_per_night', '<=', max(0, $request->integer('max_price')))
            );

        if ($stay['is_valid']) {
            $roomsQuery
                ->availableForBooking()
                ->whereDoesntHave('bookings', function (Builder $bookingQuery) use ($stay): void {
                    $bookingQuery
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->where('check_in', '<', $stay['check_out'])
                        ->where('check_out', '>', $stay['check_in']);
                });

            return;
        }

        if ($request->boolean('available_only')) {
            $roomsQuery->availableForBooking();
        }
    }

    private function resolveStayFilters(Request $request): array
    {
        $checkInInput = trim((string) $request->input('check_in', ''));
        $checkOutInput = trim((string) $request->input('check_out', ''));
        $emptyResult = [
            'check_in' => null,
            'check_out' => null,
            'nights' => null,
            'is_valid' => false,
        ];

        if ($checkInInput === '' || $checkOutInput === '') {
            return $emptyResult;
        }

        try {
            $checkIn = Carbon::createFromFormat('Y-m-d', $checkInInput)->startOfDay();
            $checkOut = Carbon::createFromFormat('Y-m-d', $checkOutInput)->startOfDay();
        } catch (Throwable) {
            return $emptyResult;
        }

        $isValid = $checkIn->greaterThanOrEqualTo(Carbon::today())
            && $checkOut->greaterThan($checkIn);

        if (!$isValid) {
            return [
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'nights' => null,
                'is_valid' => false,
            ];
        }

        return [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nights' => $checkIn->diffInDays($checkOut),
            'is_valid' => true,
        ];
    }

    private function normalizeStayDates(Request $request): ?array
    {
        $checkInInput = trim((string) $request->input('check_in', ''));
        $checkOutInput = trim((string) $request->input('check_out', ''));

        if ($checkInInput === '' || $checkOutInput === '') {
            return null;
        }

        try {
            $checkIn = Carbon::createFromFormat('Y-m-d', $checkInInput)->startOfDay();
            $checkOut = Carbon::createFromFormat('Y-m-d', $checkOutInput)->startOfDay();
        } catch (Throwable) {
            return null;
        }

        $today = Carbon::today();
        $normalizedCheckIn = $checkIn->copy();
        $normalizedCheckOut = $checkOut->copy();

        if ($normalizedCheckIn->lessThan($today)) {
            $normalizedCheckIn = $today->copy();
        }

        if ($normalizedCheckOut->lessThanOrEqualTo($normalizedCheckIn)) {
            $normalizedCheckOut = $normalizedCheckIn->copy()->addDay();
        }

        if (
            $normalizedCheckIn->equalTo($checkIn)
            && $normalizedCheckOut->equalTo($checkOut)
        ) {
            return null;
        }

        return [
            'check_in' => $normalizedCheckIn->toDateString(),
            'check_out' => $normalizedCheckOut->toDateString(),
        ];
    }

    private function attachStayPricing(Collection $rooms, array $stay): void
    {
        if (!$stay['is_valid']) {
            return;
        }

        $rooms->transform(function (Room $room) use ($stay): Room {
            $room->setAttribute(
                'stay_pricing',
                $this->pricingService->quoteStay($room, $stay['check_in'], $stay['check_out'])
            );

            return $room;
        });
    }
}

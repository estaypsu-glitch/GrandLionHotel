<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Models\RoomDateDiscount;
use App\Models\RoomStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $availability = $request->string('availability')->toString();
        if (!in_array($availability, ['all', 'available', 'unavailable'], true)) {
            $availability = 'all';
        }

        $roomStatuses = $this->roomStatusOptionsQuery()
            ->orderBy('name')
            ->get(['room_status_id', 'name', 'slug']);

        $roomStatus = trim($request->string('room_status')->toString());
        if ($roomStatus !== '' && !$roomStatuses->contains(static fn (RoomStatus $status): bool => $status->slug === $roomStatus)) {
            $roomStatus = '';
        }

        $keyword = trim($request->string('q')->toString());

        $roomsQuery = Room::query()
            ->with(['roomStatus', 'statusUpdatedByAdmin']);

        if ($keyword !== '') {
            $roomsQuery->where(function ($query) use ($keyword): void {
                $query->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('type', 'like', '%'.$keyword.'%')
                    ->orWhere('view_type', 'like', '%'.$keyword.'%');
            });
        }

        if ($availability === 'available') {
            $roomsQuery->availableForBooking();
        } elseif ($availability === 'unavailable') {
            $roomsQuery->unavailableForBooking();
        }

        if ($roomStatus !== '') {
            $roomsQuery->whereHas('roomStatus', fn ($query) => $query->where('slug', $roomStatus));
        }

        $rooms = $roomsQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Room::query()->count(),
            'available' => Room::query()->availableForBooking()->count(),
            'unavailable' => Room::query()->unavailableForBooking()->count(),
            'active_discounts' => RoomDateDiscount::query()
                ->whereDate('discount_date', '>=', now()->toDateString())
                ->count(),
        ];

        $roomTypes = Room::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $discountRoomOptions = Room::query()
            ->select(['room_id', 'name', 'type'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('admin.rooms.index', compact(
            'rooms',
            'stats',
            'roomStatuses',
            'availability',
            'roomStatus',
            'roomTypes',
            'discountRoomOptions'
        ));
    }

    public function dateDiscountsIndex(Request $request)
    {
        $from = $this->normalizeDateInput($request->string('from')->toString()) ?? now()->toDateString();
        $to = $this->normalizeDateInput($request->string('to')->toString()) ?? now()->addDays(90)->toDateString();

        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }

        $rangeStart = Carbon::parse($from)->startOfDay();
        $rangeEnd = Carbon::parse($to)->startOfDay();
        if ($rangeStart->diffInDays($rangeEnd) > 366) {
            $to = $rangeStart->copy()->addDays(366)->toDateString();
        }

        $search = trim($request->string('q')->toString());

        $discountRowsQuery = RoomDateDiscount::query()
            ->join('rooms', 'rooms.room_id', '=', 'room_date_discounts.room_id')
            ->whereDate('room_date_discounts.discount_date', '>=', $from)
            ->whereDate('room_date_discounts.discount_date', '<=', $to)
            ->select([
                'room_date_discounts.discount_date',
                'room_date_discounts.discount_percent',
                'room_date_discounts.room_id',
                'rooms.name as room_name',
                'rooms.type as room_type',
                'rooms.price_per_night as room_price_per_night',
            ]);

        if ($search !== '') {
            $discountRowsQuery->where(function ($query) use ($search): void {
                $query->where('rooms.name', 'like', '%'.$search.'%')
                    ->orWhere('rooms.type', 'like', '%'.$search.'%')
                    ->orWhere('room_date_discounts.room_id', 'like', '%'.$search.'%');
            });
        }

        $discountRawRows = $discountRowsQuery
            ->orderBy('room_date_discounts.discount_date')
            ->orderBy('room_date_discounts.room_id')
            ->get();

        $discountDailyGroups = $discountRawRows
            ->groupBy(static fn ($row): string => Carbon::parse($row->discount_date)->toDateString())
            ->map(static function ($rows, string $date): object {
                $roomIds = $rows->pluck('room_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->unique()
                    ->sort()
                    ->values();

                $roomLabels = $rows->map(static fn ($row): string => '#'.$row->room_id.' - '.trim((string) $row->room_name))
                    ->unique()
                    ->sort()
                    ->values();

                $roomTypes = $rows->pluck('room_type')
                    ->map(static fn ($type): string => trim((string) $type))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                $discountValues = $rows->pluck('discount_percent')
                    ->map(static fn ($value): string => number_format((float) $value, 2, '.', ''))
                    ->unique()
                    ->sort()
                    ->values();

                $regularPrices = $rows->pluck('room_price_per_night')
                    ->map(static fn ($value): float => round((float) $value, 2))
                    ->values();

                $discountedPrices = $rows->map(static function ($row): float {
                    $basePrice = (float) $row->room_price_per_night;
                    $percent = (float) $row->discount_percent;

                    return round($basePrice * ((100 - $percent) / 100), 2);
                })->values();

                return (object) [
                    'date' => $date,
                    'room_ids' => $roomIds,
                    'room_labels' => $roomLabels,
                    'room_types' => $roomTypes,
                    'discount_values' => $discountValues,
                    'regular_price_min' => $regularPrices->min() ?? 0.0,
                    'regular_price_max' => $regularPrices->max() ?? 0.0,
                    'discounted_price_min' => $discountedPrices->min() ?? 0.0,
                    'discounted_price_max' => $discountedPrices->max() ?? 0.0,
                    'signature' => $roomIds->join(',').'|'.$discountValues->join(','),
                ];
            })
            ->sortKeys();

        $discountOverviewRanges = collect();
        $activeRange = null;
        foreach ($discountDailyGroups as $dailyGroup) {
            $dailyDate = Carbon::parse($dailyGroup->date)->toDateString();

            if ($activeRange !== null) {
                $isNextDay = Carbon::parse($activeRange->end_date)->addDay()->toDateString() === $dailyDate;
                $sameSignature = $activeRange->signature === $dailyGroup->signature;

                if ($isNextDay && $sameSignature) {
                    $activeRange->end_date = $dailyDate;
                    continue;
                }

                $discountOverviewRanges->push($activeRange);
            }

            $activeRange = (object) [
                'start_date' => $dailyDate,
                'end_date' => $dailyDate,
                'room_ids' => $dailyGroup->room_ids,
                'room_labels' => $dailyGroup->room_labels,
                'room_types' => $dailyGroup->room_types,
                'discount_values' => $dailyGroup->discount_values,
                'regular_price_min' => (float) $dailyGroup->regular_price_min,
                'regular_price_max' => (float) $dailyGroup->regular_price_max,
                'discounted_price_min' => (float) $dailyGroup->discounted_price_min,
                'discounted_price_max' => (float) $dailyGroup->discounted_price_max,
                'signature' => $dailyGroup->signature,
            ];
        }

        if ($activeRange !== null) {
            $discountOverviewRanges->push($activeRange);
        }

        $summary = [
            'entry_count' => $discountRawRows->count(),
            'date_count' => $discountRawRows->pluck('discount_date')->map(static fn ($date) => Carbon::parse($date)->toDateString())->unique()->count(),
            'room_count' => $discountRawRows->pluck('room_id')->unique()->count(),
        ];

        return view('admin.rooms.date-discounts', compact(
            'discountOverviewRanges',
            'from',
            'to',
            'search',
            'summary'
        ));
    }

    public function updateDateDiscountRange(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'room_ids' => ['required', 'array', 'min:1'],
            'room_ids.*' => ['integer', 'exists:rooms,room_id'],
            'discount_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
        ], [
            'room_ids.required' => 'Missing affected rooms for this discount.',
            'room_ids.min' => 'At least one room is required.',
            'discount_percent.required' => 'Please enter a discount percentage.',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay();
        if ($start->diffInDays($end) > 366) {
            return back()
                ->withErrors(['discount_percent' => 'Date range cannot exceed 366 days.'])
                ->withInput();
        }

        $roomIds = collect((array) $validated['room_ids'])
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $discountPercent = round((float) $validated['discount_percent'], 2);
        $affectedRows = RoomDateDiscount::query()
            ->whereIn('room_id', $roomIds)
            ->whereDate('discount_date', '>=', $start->toDateString())
            ->whereDate('discount_date', '<=', $end->toDateString())
            ->update([
                'discount_percent' => $discountPercent,
                'admin_id' => (int) $request->user()->id,
                'updated_at' => now(),
            ]);

        $query = array_filter([
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'q' => isset($validated['q']) ? trim((string) $validated['q']) : null,
        ], static fn ($value) => $value !== null && $value !== '');

        return redirect()
            ->route('admin.rooms.date-discounts.index', $query)
            ->with(
                'status',
                $affectedRows > 0
                    ? "Updated {$affectedRows} discounted room-date entries to {$discountPercent}%."
                    : 'No matching date discounts were updated.'
            );
    }

    public function create()
    {
        $roomStatuses = $this->roomStatusOptionsQuery()
            ->orderBy('name')
            ->get();

        return view('admin.rooms.create', compact('roomStatuses'));
    }

    public function store(StoreRoomRequest $request)
    {
        $data = $request->validated();

        // Default new rooms to the ready-for-use room status.
        if (!isset($data['room_status_id'])) {
            $cleanStatus = RoomStatus::where('slug', 'clean')->first();
            if ($cleanStatus) {
                $data['room_status_id'] = $cleanStatus->id;
            }
        }

        if (isset($data['room_status_id'])) {
            $data['admin_id'] = $request->user()->id;
            $data['status_updated_at'] = now();
        }

        Room::create($data);

        return redirect()->route('admin.rooms.index')->with('status', 'Room created successfully.');
    }

    public function edit(Room $room)
    {
        $roomStatuses = $this->roomStatusOptionsQuery()
            ->orderBy('name')
            ->get();

        return view('admin.rooms.edit', compact('room', 'roomStatuses'));
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        $data = $request->validated();

        if (array_key_exists('room_status_id', $data) && (int) $data['room_status_id'] !== (int) $room->room_status_id) {
            $data['admin_id'] = $request->user()->id;
            $data['status_updated_at'] = now();
        }

        $room->update($data);

        return redirect()->route('admin.rooms.index')->with('status', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return redirect()->route('admin.rooms.index')->with('status', 'Room deleted successfully.');
    }
    
    public function updateRoomStatus(Request $request, Room $room)
    {
        $validated = $request->validate([
            'room_status_id' => 'required|exists:room_status,room_status_id',
        ]);

        $status = $this->roomStatusOptionsQuery()->findOrFail((int) $validated['room_status_id']);
        
        $room->update([
            'room_status_id' => $status->id,
            'admin_id' => $request->user()->id,
            'status_updated_at' => now(),
        ]);
        
        return back()->with('status', 'Room status updated successfully. Booking availability synced automatically.');
    }

    public function applyBulkDateDiscount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_scope' => ['required', 'in:all,roomtype,selected'],
            'room_type' => ['nullable', 'string', 'max:100'],
            'room_ids' => ['nullable', 'array'],
            'room_ids.*' => ['integer', 'exists:rooms,room_id'],
            'discount_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'discount_start' => ['required', 'date'],
            'discount_end' => ['required', 'date', 'after_or_equal:discount_start'],
        ], [
            'target_scope.required' => 'Please choose how rooms will be targeted.',
            'target_scope.in' => 'Selected room targeting option is invalid.',
            'discount_percent.required' => 'Please enter a discount percentage.',
            'discount_percent.min' => 'Discount must be at least 1%.',
            'discount_percent.max' => 'Discount cannot exceed 100%.',
        ]);

        $validator->after(function ($validator) use ($request): void {
            $scope = (string) $request->input('target_scope', '');

            if ($scope === 'roomtype' && trim((string) $request->input('room_type', '')) === '') {
                $validator->errors()->add('room_type', 'Please choose a room type.');
            }

            if ($scope === 'selected' && empty((array) $request->input('room_ids', []))) {
                $validator->errors()->add('room_ids', 'Please choose at least one room.');
            }

            $startRaw = (string) $request->input('discount_start', '');
            $endRaw = (string) $request->input('discount_end', '');
            if ($startRaw === '' || $endRaw === '') {
                return;
            }

            try {
                $start = Carbon::parse($startRaw)->startOfDay();
                $end = Carbon::parse($endRaw)->startOfDay();
                if ($start->diffInDays($end) > 365) {
                    $validator->errors()->add('discount_end', 'Date range cannot exceed 366 days.');
                }
            } catch (\Throwable) {
                // Base validator already handles invalid date format.
            }
        });

        $validated = $validator->validate();

        $scope = $validated['target_scope'];
        $roomIds = [];

        if ($scope === 'all') {
            $roomIds = Room::query()->pluck('room_id')->map(fn ($id) => (int) $id)->all();
        } elseif ($scope === 'roomtype') {
            $roomType = trim((string) ($validated['room_type'] ?? ''));
            $roomIds = Room::query()
                ->whereRaw('LOWER(type) = ?', [Str::lower($roomType)])
                ->pluck('room_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } elseif ($scope === 'selected') {
            $roomIds = collect((array) ($validated['room_ids'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if ($roomIds === []) {
            return back()
                ->withInput()
                ->withErrors(['target_scope' => 'No rooms found for the selected target.']);
        }

        $start = Carbon::parse($validated['discount_start'])->startOfDay();
        $end = Carbon::parse($validated['discount_end'])->startOfDay();
        $dates = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $discountPercent = round((float) $validated['discount_percent'], 2);
        $adminId = (int) $request->user()->id;
        $now = now();
        $rows = [];

        foreach ($roomIds as $roomId) {
            foreach ($dates as $discountDate) {
                $rows[] = [
                    'room_id' => $roomId,
                    'discount_date' => $discountDate,
                    'discount_percent' => $discountPercent,
                    'admin_id' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        RoomDateDiscount::query()->upsert(
            $rows,
            ['room_id', 'discount_date'],
            ['discount_percent', 'admin_id', 'updated_at']
        );

        $roomCount = count($roomIds);
        $dayCount = count($dates);

        return redirect()
            ->route('admin.rooms.index')
            ->with('status', "Discount applied: {$discountPercent}% for {$roomCount} room(s) across {$dayCount} day(s).");
    }

    private function roomStatusOptionsQuery()
    {
        return RoomStatus::query()->where('slug', '!=', 'needs_cleaning');
    }

    private function normalizeDateInput(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        try {
            return Carbon::parse($trimmed)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}

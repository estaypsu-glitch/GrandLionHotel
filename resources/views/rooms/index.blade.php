@extends('layouts.app')

@section('title', 'Rooms')

@push('head')
    <style>
        .rooms-filter-shell {
            border-radius: 22px;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.1);
        }
        .rooms-selected-stay {
            border-radius: 16px;
            border: 1px solid rgba(184, 146, 84, 0.34);
            background: linear-gradient(135deg, rgba(184, 146, 84, 0.12) 0%, rgba(255, 255, 255, 0.96) 100%);
            padding: 0.82rem 0.95rem;
            margin-top: 0.9rem;
        }
        .rooms-selected-stay .meta {
            font-size: 0.78rem;
            color: #5f6674;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 0.2rem;
        }
        .rooms-active-wrap {
            margin-top: 0.85rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .rooms-chip {
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #faf5ed;
            color: #455063;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.24rem 0.66rem;
        }
        .rooms-chip strong {
            color: #1f2937;
            font-weight: 800;
        }
        .rooms-results-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.65rem;
            margin-bottom: 0.95rem;
        }
        .rooms-count {
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            color: #4b5563;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.26rem 0.64rem;
        }
        .rooms-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            justify-content: flex-end;
        }
    </style>
@endpush

@section('content')
    @php
        $stay = $stay ?? [
            'check_in' => null,
            'check_out' => null,
            'nights' => null,
            'is_valid' => false,
        ];
        $activeFilters = [];
        if (filled(request('type'))) {
            $activeFilters[] = ['label' => 'Type', 'value' => request('type')];
        }
        if (filled(request('guests'))) {
            $activeFilters[] = ['label' => 'Guests', 'value' => request('guests')];
        }
        if (filled(request('max_price'))) {
            $activeFilters[] = ['label' => 'Max Price', 'value' => 'PHP '.number_format((int) request('max_price'))];
        }
        if (filled(request('sort'))) {
            $activeFilters[] = ['label' => 'Sort', 'value' => ucfirst(str_replace('_', ' ', (string) request('sort')))];
        }
        if (request('available_only')) {
            $activeFilters[] = ['label' => 'Availability', 'value' => 'Available only'];
        }
    @endphp

    <section class="rooms-filter-shell p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('rooms.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-4 col-xl-3">
                    <label class="form-label">Room type or view</label>
                    <input type="text" name="type" class="form-control" value="{{ request('type') }}" placeholder="Standard, Deluxe, Nature View">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Guests</label>
                    <input type="number" name="guests" min="1" class="form-control" value="{{ request('guests') }}" placeholder="2">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Check-in</label>
                    <input type="date" name="check_in" min="{{ now()->toDateString() }}" class="form-control" value="{{ request('check_in') }}">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Check-out</label>
                    <input type="date" name="check_out" min="{{ now()->addDay()->toDateString() }}" class="form-control" value="{{ request('check_out') }}">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Max &#8369;/night</label>
                    <input type="number" name="max_price" min="0" step="100" class="form-control" value="{{ request('max_price') }}" placeholder="5000">
                </div>
                <div class="col-md-4 col-xl-3">
                    <label class="form-label">Sort</label>
                    <select class="form-select" name="sort">
                        <option value="recommended" @selected(request('sort', 'recommended') === 'recommended')>Recommended</option>
                        <option value="price_low" @selected(request('sort') === 'price_low')>Price: Low to High</option>
                        <option value="price_high" @selected(request('sort') === 'price_high')>Price: High to Low</option>
                        <option value="capacity" @selected(request('sort') === 'capacity')>Highest Capacity</option>
                        <option value="newest" @selected(request('sort') === 'newest')>Newest Listings</option>
                    </select>
                </div>
                <div class="col-md-4 col-xl-2 d-grid gap-2">
                    <button type="submit" class="btn btn-ta">Apply filters</button>
                    @if(request()->query())
                        <a href="{{ route('rooms.index') }}" class="btn btn-ta-outline">Reset</a>
                    @endif
                </div>
                <div class="col-12">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="available_only" value="1" id="availableOnlyRooms" {{ request('available_only') ? 'checked' : '' }}>
                        <label class="form-check-label" for="availableOnlyRooms">Show available rooms only</label>
                    </div>
                </div>
            </div>
        </form>

        @if($stay['is_valid'])
            <div class="rooms-selected-stay">
                <p class="meta mb-0">Selected Stay</p>
                <div class="small">
                    <strong>{{ \Carbon\Carbon::parse($stay['check_in'])->format('M d, Y') }}</strong>
                    to
                    <strong>{{ \Carbon\Carbon::parse($stay['check_out'])->format('M d, Y') }}</strong>
                    <span class="text-secondary ms-1">({{ $stay['nights'] }} night{{ $stay['nights'] === 1 ? '' : 's' }})</span>
                </div>
            </div>
        @elseif(filled(request('check_in')) || filled(request('check_out')))
            <div class="alert alert-warning mb-0 mt-3">
                Select a valid check-in/check-out date range to see real-time availability.
            </div>
        @endif

        @if(!empty($activeFilters))
            <div class="rooms-active-wrap">
                @foreach($activeFilters as $filter)
                    <span class="rooms-chip">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong></span>
                @endforeach
            </div>
        @endif
    </section>

    <div class="rooms-results-head">
        <h2 class="h5 mb-0">Room Results</h2>
        <span class="rooms-count">{{ $rooms->total() }} room{{ $rooms->total() === 1 ? '' : 's' }} found</span>
    </div>

    <div class="row g-4">
        @forelse($rooms as $room)
            <div class="col-md-6 col-xl-4">
                <article class="soft-card h-100 result-card overflow-hidden">
                    <a href="{{ route('rooms.show', $room) }}" aria-label="View {{ $room->name }} details">
                        <img src="{{ $room->image_url }}" alt="{{ $room->name }}" class="w-100 object-cover" style="height: 220px;">
                    </a>
                    <div class="p-3 p-lg-4 d-flex flex-column h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h2 class="h5 mb-1">
                                    <a href="{{ route('rooms.show', $room) }}" class="text-decoration-none text-dark">{{ $room->name }}</a>
                                </h2>
                                <p class="hotel-meta mb-0">
                                    {{ $room->type }}
                                    @if(filled($room->view_type))
                                        &middot; {{ $room->view_type }}
                                    @endif
                                    &middot; Up to {{ $room->capacity }} guests
                                </p>
                            </div>
                            <span class="badge-status {{ $room->is_available ? 'available' : 'unavailable' }}">
                                {{ $room->is_available ? 'Available' : 'Unavailable' }}
                            </span>
                        </div>
                        <p class="text-secondary small mb-3">{{ \Illuminate\Support\Str::limit($room->description ?: 'Clean and practical room setup for short and long stays.', 95) }}</p>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <div>
                                <div class="price-tag">&#8369;{{ number_format($room->price_per_night, 2) }}</div>
                                <small class="text-secondary">per night</small>
                            </div>
                            <div class="rooms-card-actions">
                                <a href="{{ route('rooms.show', $room) }}" class="btn btn-ta-outline btn-sm">Details</a>
                                @if($stay['is_valid'] && $room->is_available)
                                    @auth
                                        <a
                                            href="{{ route('bookings.create', ['room' => $room, 'check_in' => $stay['check_in'], 'check_out' => $stay['check_out'], 'guests' => max(1, (int) request('guests', 1))]) }}"
                                            class="btn btn-ta btn-sm"
                                        >
                                            Book now
                                        </a>
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-ta btn-sm">Sign in to book</a>
                                    @endauth
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info soft-card border-0 mb-0">No rooms matched your filter criteria. Try broader filters.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $rooms->links() }}
    </div>
@endsection

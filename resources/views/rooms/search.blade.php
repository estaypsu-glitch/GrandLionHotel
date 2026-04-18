@extends('layouts.app')

@section('title', 'Room Search')

@push('head')
    <style>
        .search-hero {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            border: 1px solid var(--line);
            background:
                radial-gradient(circle at 8% 10%, rgba(255, 255, 255, 0.24) 0, rgba(255, 255, 255, 0) 38%),
                radial-gradient(circle at 90% 18%, rgba(255, 220, 160, 0.44) 0, rgba(255, 220, 160, 0) 44%),
                linear-gradient(130deg, var(--brand-deep) 0%, #a68449 54%, var(--brand) 100%);
            color: #f8fbff;
            box-shadow: 0 22px 42px rgba(15, 23, 42, 0.2);
        }
        .search-hero::after {
            content: '';
            position: absolute;
            right: -58px;
            bottom: -80px;
            width: 230px;
            height: 230px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.11);
        }
        .search-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.1);
            padding: 0.28rem 0.75rem;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .search-filter-shell {
            border-radius: 22px;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.1);
        }
        .search-filter-grid {
            display: grid;
            gap: 0.8rem;
            grid-template-columns: repeat(15, minmax(0, 1fr));
        }
        .search-filter-grid .field-type { grid-column: span 3; }
        .search-filter-grid .field-guests { grid-column: span 2; }
        .search-filter-grid .field-check-in { grid-column: span 2; }
        .search-filter-grid .field-check-out { grid-column: span 2; }
        .search-filter-grid .field-max-price { grid-column: span 2; }
        .search-filter-grid .field-sort { grid-column: span 4; }
        .search-filter-grid .field-availability { grid-column: span 8; align-self: end; }
        .search-filter-grid .field-actions { grid-column: span 7; align-self: end; }
        .search-filter-actions {
            display: flex;
            gap: 0.5rem;
        }
        .search-filter-actions .btn {
            flex: 1;
        }
        .search-selected-stay {
            border-radius: 14px;
            border: 1px solid rgba(184, 146, 84, 0.3);
            background: linear-gradient(135deg, rgba(184, 146, 84, 0.11) 0%, rgba(255, 255, 255, 0.96) 100%);
            padding: 0.75rem 0.86rem;
        }
        .search-selected-stay .meta {
            font-size: 0.73rem;
            color: #616a79;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.14rem;
        }
        .search-active-wrap {
            margin-top: 0.85rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .search-chip {
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #faf5ed;
            color: #455063;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.24rem 0.66rem;
        }
        .search-chip strong {
            color: #1f2937;
            font-weight: 800;
        }
        .search-results-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.65rem;
            margin-bottom: 0.95rem;
        }
        .search-count {
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            color: #4b5563;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.26rem 0.64rem;
        }
        .search-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            justify-content: flex-end;
        }
        @media (max-width: 1199.98px) {
            .search-filter-grid {
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }
            .search-filter-grid .field-type,
            .search-filter-grid .field-sort {
                grid-column: span 4;
            }
            .search-filter-grid .field-guests,
            .search-filter-grid .field-check-in,
            .search-filter-grid .field-check-out,
            .search-filter-grid .field-max-price {
                grid-column: span 2;
            }
            .search-filter-grid .field-availability,
            .search-filter-grid .field-actions {
                grid-column: span 4;
            }
        }
        @media (max-width: 991.98px) {
            .search-filter-grid .field-type,
            .search-filter-grid .field-guests,
            .search-filter-grid .field-check-in,
            .search-filter-grid .field-check-out,
            .search-filter-grid .field-max-price,
            .search-filter-grid .field-sort,
            .search-filter-grid .field-availability,
            .search-filter-grid .field-actions {
                grid-column: span 6;
            }
        }
        @media (max-width: 575.98px) {
            .search-filter-grid .field-type,
            .search-filter-grid .field-guests,
            .search-filter-grid .field-check-in,
            .search-filter-grid .field-check-out,
            .search-filter-grid .field-max-price,
            .search-filter-grid .field-sort,
            .search-filter-grid .field-availability,
            .search-filter-grid .field-actions {
                grid-column: span 12;
            }
            .search-filter-actions {
                flex-direction: column;
            }
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

    <section class="search-hero p-4 p-lg-5 mb-4">
        <div class="position-relative d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <span class="search-tag"><i class="bi bi-stars"></i> Curated Search</span>
                <h1 class="h3 mt-2 mb-2">Find Your Best Match</h1>
                <p class="text-white-50 mb-0">Use focused filters and compare premium room options quickly.</p>
            </div>
            <a href="{{ route('rooms.index') }}" class="btn btn-ta-outline">Back to all rooms</a>
        </div>
    </section>

    <section class="search-filter-shell p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('rooms.search') }}" class="search-filter-grid">
            <div class="field-type">
                <label class="form-label">Room type or view</label>
                <input type="text" name="type" class="form-control" value="{{ request('type') }}" placeholder="Suite, Deluxe, Nature View">
            </div>
            <div class="field-guests">
                <label class="form-label">Guests</label>
                <input type="number" name="guests" min="1" class="form-control" value="{{ request('guests') }}">
            </div>
            <div class="field-check-in">
                <label class="form-label">Check-in</label>
                <input type="date" name="check_in" min="{{ now()->toDateString() }}" class="form-control" value="{{ request('check_in') }}">
            </div>
            <div class="field-check-out">
                <label class="form-label">Check-out</label>
                <input type="date" name="check_out" min="{{ now()->addDay()->toDateString() }}" class="form-control" value="{{ request('check_out') }}">
            </div>
            <div class="field-max-price">
                <label class="form-label">Max &#8369;/night</label>
                <input type="number" name="max_price" min="0" step="100" class="form-control" value="{{ request('max_price') }}" placeholder="5000">
            </div>
            <div class="field-sort">
                <label class="form-label">Sort by</label>
                <select class="form-select" name="sort">
                    <option value="recommended" @selected(request('sort', 'recommended') === 'recommended')>Recommended</option>
                    <option value="price_low" @selected(request('sort') === 'price_low')>Price: Low to High</option>
                    <option value="price_high" @selected(request('sort') === 'price_high')>Price: High to Low</option>
                    <option value="capacity" @selected(request('sort') === 'capacity')>Highest Capacity</option>
                    <option value="newest" @selected(request('sort') === 'newest')>Newest Listings</option>
                </select>
            </div>
            <div class="field-availability">
                <div class="form-check mt-3 mt-md-0">
                    <input class="form-check-input" type="checkbox" name="available_only" value="1" id="availableOnly" {{ request('available_only') ? 'checked' : '' }}>
                    <label class="form-check-label" for="availableOnly">Available only</label>
                </div>
            </div>
            <div class="field-actions">
                <div class="search-filter-actions">
                    <button type="submit" class="btn btn-ta">Apply Filters</button>
                    @if(request()->query())
                        <a href="{{ route('rooms.search') }}" class="btn btn-ta-outline">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        @if($stay['is_valid'])
            <div class="search-selected-stay mt-3">
                <p class="meta mb-0">Selected Stay</p>
                <div class="small">
                    <strong>{{ \Carbon\Carbon::parse($stay['check_in'])->format('M d, Y') }}</strong>
                    to
                    <strong>{{ \Carbon\Carbon::parse($stay['check_out'])->format('M d, Y') }}</strong>
                    <span class="text-secondary ms-1">({{ $stay['nights'] }} night{{ $stay['nights'] === 1 ? '' : 's' }})</span>
                </div>
            </div>
        @elseif(filled(request('check_in')) || filled(request('check_out')))
            <div class="alert alert-warning mb-0 mt-3 py-2 small">
                Select a valid date range to apply real-time availability filtering.
            </div>
        @endif

        @if(!empty($activeFilters))
            <div class="search-active-wrap">
                @foreach($activeFilters as $filter)
                    <span class="search-chip">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong></span>
                @endforeach
            </div>
        @endif
    </section>

    <div class="search-results-head">
        <h2 class="h5 mb-0">Search Results</h2>
        <span class="search-count">{{ $rooms->total() }} room{{ $rooms->total() === 1 ? '' : 's' }} found</span>
    </div>

    <div class="row g-4">
        @forelse($rooms as $room)
            <div class="col-md-6 col-xl-4">
                <article class="soft-card h-100 result-card overflow-hidden">
                    <a href="{{ route('rooms.show', $room) }}" aria-label="View {{ $room->name }} details">
                        <img src="{{ $room->image_url }}" alt="{{ $room->name }}" class="w-100 object-cover" style="height: 220px;">
                    </a>
                    <div class="p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h2 class="h5 mb-1"><a href="{{ route('rooms.show', $room) }}" class="text-decoration-none text-dark">{{ $room->name }}</a></h2>
                                <p class="hotel-meta mb-0">
                                    {{ $room->type }}
                                    @if(filled($room->view_type))
                                        &middot; {{ $room->view_type }}
                                    @endif
                                    &middot; Up to {{ $room->capacity }} guests
                                </p>
                            </div>
                            <span class="badge-status {{ $room->is_available ? 'available' : 'unavailable' }}">{{ $room->is_available ? 'Available' : 'Unavailable' }}</span>
                        </div>
                        <p class="text-secondary small mb-3">{{ \Illuminate\Support\Str::limit($room->description ?: 'Comfortable stay with practical in-room amenities.', 85) }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="price-tag">&#8369;{{ number_format($room->price_per_night, 2) }}</div>
                                <small class="text-secondary">per night</small>
                            </div>
                            <div class="search-card-actions">
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
                <div class="alert alert-info soft-card border-0 mb-0">No rooms matched your filters. Try broader criteria.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $rooms->links() }}
    </div>
@endsection

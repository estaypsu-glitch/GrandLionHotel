@extends('layouts.app')

@section('title', $room->name)

@push('head')
    <style>
        .room-hero-image {
            width: 100%;
            height: clamp(280px, 46vw, 500px);
            object-fit: cover;
        }
        .room-type-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            border: 1px solid rgba(184, 146, 84, 0.36);
            background: rgba(184, 146, 84, 0.12);
            color: #75582e;
            padding: 0.26rem 0.72rem;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }
        .room-feature-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.6rem;
        }
        .room-feature {
            border-radius: 12px;
            border: 1px solid #ebdfcd;
            background: #fff;
            padding: 0.65rem 0.75rem;
            font-size: 0.86rem;
            color: #374151;
            font-weight: 600;
        }
        .room-booking-panel {
            border-radius: 20px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, #fff 0%, #fbf6ed 100%);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.1);
            position: static;
            z-index: 1;
        }
        @media (min-width: 992px) {
            .room-booking-panel {
                position: sticky;
                top: clamp(74px, 7vh, 96px);
                max-height: calc(100vh - clamp(74px, 7vh, 96px) - 1rem);
                overflow-y: auto;
            }
        }
        @media (max-width: 575.98px) {
            .room-feature-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 991.98px) {
            .room-booking-panel {
                max-height: none;
                overflow: visible;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $checkIn = (string) request('check_in', now()->toDateString());
        $checkOut = (string) request('check_out', now()->addDay()->toDateString());
        if ($checkOut === '') {
            $checkOut = now()->addDay()->toDateString();
        }
        $minimumCheckOut = now()->addDay()->toDateString();
        $guests = max(1, min($room->capacity, (int) request('guests', 1)));
    @endphp

    <div class="row g-4">
        <div class="col-lg-8">
            <article class="soft-card overflow-hidden">
                <img src="{{ $room->image_url }}" alt="{{ $room->name }}" class="room-hero-image">
                <div class="p-4 p-lg-5">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <span class="room-type-chip">Room details</span>
                            <h1 class="mb-1 mt-2">{{ $room->name }}</h1>
                            <p class="hotel-meta mb-0">
                                {{ $room->type }}
                                @if(filled($room->view_type))
                                    &middot; {{ $room->view_type }}
                                @endif
                                &middot; Up to {{ $room->capacity }} guests
                            </p>
                        </div>
                        <span class="badge-status {{ $room->is_available ? 'available' : 'unavailable' }}">
                            {{ $room->is_available ? 'Available now' : 'Currently unavailable' }}
                        </span>
                    </div>

                    <p class="text-secondary mb-4">
                        {{ $room->description ?: 'This room is designed for comfort and practical travel needs, with clear pricing and a smooth reservation process.' }}
                    </p>

                    <div class="room-feature-grid">
                        <div class="room-feature"><i class="bi bi-person-check me-1"></i> Capacity: {{ $room->capacity }} guest{{ $room->capacity === 1 ? '' : 's' }}</div>
                        <div class="room-feature"><i class="bi bi-grid-1x2 me-1"></i> Type: {{ $room->type }}</div>
                        <div class="room-feature"><i class="bi bi-tree me-1"></i> View: {{ $room->view_type ?: 'Not specified' }}</div>
                        <div class="room-feature"><i class="bi bi-shield-check me-1"></i> Staff-verified reservation flow</div>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-lg-4">
            <aside class="room-booking-panel p-4">
                <p class="ta-eyebrow mb-1">Start Reservation</p>
                <div class="price-tag mb-1">&#8369;{{ number_format($room->price_per_night, 2) }}</div>
                <p class="text-secondary small mb-3">per night</p>

                <ul class="list-unstyled small text-secondary mb-4">
                    <li class="mb-2">Room type: <strong class="text-dark">{{ $room->type }}</strong></li>
                    <li class="mb-2">View: <strong class="text-dark">{{ $room->view_type ?: 'Not specified' }}</strong></li>
                    <li class="mb-2">Capacity: <strong class="text-dark">{{ $room->capacity }} guest{{ $room->capacity === 1 ? '' : 's' }}</strong></li>
                    <li>Status: <strong class="text-dark">{{ $room->is_available ? 'Available' : 'Unavailable' }}</strong></li>
                </ul>

                @if($room->is_available)
                    @auth
                        <form method="GET" action="{{ route('bookings.create', $room) }}" class="d-grid gap-2" id="room_quick_booking_form">
                            <div>
                                <label class="form-label small mb-1">Check-in</label>
                                <input type="date" class="form-control" name="check_in" id="room_check_in_input" min="{{ now()->toDateString() }}" value="{{ $checkIn }}" required>
                            </div>
                            <div>
                                <label class="form-label small mb-1">Check-out</label>
                                <input type="date" class="form-control" name="check_out" id="room_check_out_input" min="{{ $minimumCheckOut }}" value="{{ $checkOut }}" required>
                            </div>
                            <div>
                                <label class="form-label small mb-1">Guests</label>
                                <input type="number" class="form-control" name="guests" min="1" max="{{ $room->capacity }}" value="{{ $guests }}" required>
                            </div>
                            <br>
                            <button type="submit" class="btn btn-ta w-100">Continue to booking form</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ta w-100">Sign in to continue</a>
                    @endauth
                @else
                    <button class="btn btn-secondary w-100" disabled>Unavailable for booking</button>
                @endif

                <a href="{{ route('rooms.index') }}" class="btn btn-ta-outline w-100 mt-2">Back to rooms</a>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const checkInInput = document.getElementById('room_check_in_input');
            const checkOutInput = document.getElementById('room_check_out_input');

            if (!checkInInput || !checkOutInput) {
                return;
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const formatDate = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            const parseInputDate = (value) => {
                if (!value) {
                    return null;
                }
                const parsed = new Date(`${value}T00:00:00`);
                return Number.isNaN(parsed.getTime()) ? null : parsed;
            };

            const applyDateRules = () => {
                const selectedCheckIn = parseInputDate(checkInInput.value) ?? today;
                const checkInBase = selectedCheckIn < today ? today : selectedCheckIn;
                const minCheckoutDate = new Date(checkInBase);
                minCheckoutDate.setDate(minCheckoutDate.getDate() + 1);
                const minCheckOut = formatDate(minCheckoutDate);
                checkOutInput.min = minCheckOut;

                if (!checkOutInput.value || checkOutInput.value < minCheckOut) {
                    checkOutInput.value = minCheckOut;
                }
            };

            checkInInput.addEventListener('change', applyDateRules);
            checkOutInput.addEventListener('change', applyDateRules);
            applyDateRules();
        })();
    </script>
@endpush

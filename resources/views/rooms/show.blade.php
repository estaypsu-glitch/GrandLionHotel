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
        $stay = $stay ?? [
            'check_in' => null,
            'check_out' => null,
            'nights' => null,
            'is_valid' => false,
        ];
        $pricingPreview = $pricingPreview ?? null;
        $stayAvailability = $stayAvailability ?? $room->is_available;
        $checkIn = (string) request('check_in', now()->toDateString());
        $checkOut = (string) request('check_out', now()->addDay()->toDateString());
        $standardGuests = \App\Models\Room::standardGuestCapacity();
        if ($checkOut === '') {
            $checkOut = now()->addDay()->toDateString();
        }
        $minimumCheckOut = now()->addDay()->toDateString();
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
                                &middot; Standard occupancy: {{ $standardGuests }} guests
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
                        <div class="room-feature"><i class="bi bi-person-check me-1"></i> Standard occupancy: {{ $standardGuests }} guests</div>
                        <div class="room-feature"><i class="bi bi-grid-1x2 me-1"></i> Type: {{ $room->type }}</div>
                        <div class="room-feature"><i class="bi bi-tree me-1"></i> View: {{ $room->view_type ?: 'Not specified' }}</div>
                        <div class="room-feature"><i class="bi bi-shield-check me-1"></i> Extra bedding available upon request</div>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-lg-4">
            <aside class="room-booking-panel p-4">
                <p class="ta-eyebrow mb-1">Start Reservation</p>
                <div class="price-tag mb-1" id="room_headline_rate">
                    &#8369;{{ number_format($pricingPreview['average_nightly_rate'] ?? $room->price_per_night, 2) }}
                </div>
                <small class="text-secondary d-block" id="room_price_caption">
                    {{ $pricingPreview ? 'selected-stay average / night' : 'per night' }}
                </small>
                <p class="small mb-3 {{ $pricingPreview && $pricingPreview['has_date_discount'] ? '' : 'd-none' }}" id="room_base_rate_wrap">
                    <span class="text-secondary text-decoration-line-through" id="room_base_rate">
                        &#8369;{{ number_format($room->price_per_night, 2) }}
                    </span>
                    <span class="text-success ms-2" id="room_discount_note">
                        @if($pricingPreview && $pricingPreview['has_date_discount'])
                            Date discount on {{ $pricingPreview['discounted_nights'] }} night{{ $pricingPreview['discounted_nights'] === 1 ? '' : 's' }}
                            &middot; Save &#8369;{{ number_format($pricingPreview['discount_amount'], 2) }}
                        @endif
                    </span>
                </p>

                <ul class="list-unstyled small text-secondary mb-4">
                    <li class="mb-2">Room type: <strong class="text-dark">{{ $room->type }}</strong></li>
                    <li class="mb-2">View: <strong class="text-dark">{{ $room->view_type ?: 'Not specified' }}</strong></li>
                    <li class="mb-2">Occupancy: <strong class="text-dark">{{ $standardGuests }} guests standard</strong></li>
                    <li class="mb-2">Extra bedding: <strong class="text-dark">Available by request</strong></li>
                    <li class="mb-2">
                        Selected stay:
                        <strong class="text-dark" id="room_stay_value">
                            @if($pricingPreview)
                                {{ \Carbon\Carbon::parse($pricingPreview['check_in'])->format('M d, Y') }}
                                -
                                {{ \Carbon\Carbon::parse($pricingPreview['check_out'])->format('M d, Y') }}
                                ({{ $pricingPreview['nights'] }} night{{ $pricingPreview['nights'] === 1 ? '' : 's' }})
                            @else
                                Select dates below
                            @endif
                        </strong>
                    </li>
                    <li class="mb-2">
                        Selected stay total:
                        <strong class="text-dark" id="room_total_value">
                            @if($pricingPreview)
                                &#8369;{{ number_format($pricingPreview['total'], 2) }}
                            @else
                                Select dates to preview
                            @endif
                        </strong>
                    </li>
                    <li>
                        Status:
                        <strong class="text-dark" id="room_availability_status">
                            @if($pricingPreview)
                                {{ $stayAvailability ? 'Available for selected dates' : 'Unavailable for selected dates' }}
                            @else
                                {{ $room->is_available ? 'Available' : 'Unavailable' }}
                            @endif
                        </strong>
                    </li>
                </ul>

                @if($room->is_available)
                    @auth
                        <form
                            method="GET"
                            action="{{ route('bookings.create', $room) }}"
                            class="d-grid gap-2"
                            id="room_quick_booking_form"
                            data-preview-url="{{ route('rooms.pricing-preview', $room) }}"
                        >
                            <div>
                                <label class="form-label small mb-1">Check-in</label>
                                <input type="date" class="form-control" name="check_in" id="room_check_in_input" min="{{ now()->toDateString() }}" value="{{ $checkIn }}" required>
                            </div>
                            <div>
                                <label class="form-label small mb-1">Check-out</label>
                                <input type="date" class="form-control" name="check_out" id="room_check_out_input" min="{{ $minimumCheckOut }}" value="{{ $checkOut }}" required>
                            </div>
                            <p class="small text-secondary mb-1">
                                Standard room setup is for {{ $standardGuests }} guests. Extra bedding can be requested during booking.
                            </p>
                            <br>
                            <button type="submit" class="btn btn-ta w-100" id="room_booking_submit">Continue to booking form</button>
                        </form>
                        <div class="small mt-2 text-secondary" id="room_booking_feedback" aria-live="polite"></div>
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
            const form = document.getElementById('room_quick_booking_form');
            const checkInInput = document.getElementById('room_check_in_input');
            const checkOutInput = document.getElementById('room_check_out_input');
            const headlineRate = document.getElementById('room_headline_rate');
            const priceCaption = document.getElementById('room_price_caption');
            const baseRateWrap = document.getElementById('room_base_rate_wrap');
            const baseRate = document.getElementById('room_base_rate');
            const discountNote = document.getElementById('room_discount_note');
            const stayValue = document.getElementById('room_stay_value');
            const totalValue = document.getElementById('room_total_value');
            const availabilityStatus = document.getElementById('room_availability_status');
            const bookingFeedback = document.getElementById('room_booking_feedback');
            const bookingSubmit = document.getElementById('room_booking_submit');
            const baseNightlyRate = Number.parseFloat('{{ number_format((float) $room->price_per_night, 2, '.', '') }}') || 0;

            if (!form || !checkInInput || !checkOutInput) {
                return;
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const dateFormatter = new Intl.DateTimeFormat('en-PH', {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
            });
            const currencyFormatter = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                maximumFractionDigits: 2,
            });

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

            const formatCurrency = (value) => currencyFormatter.format(Math.max(0, Number(value) || 0));

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

            const setAvailabilityState = (availability, fallbackMessage = 'Select valid dates to preview.') => {
                if (availabilityStatus) {
                    availabilityStatus.textContent = availability?.message || fallbackMessage;
                }

                if (bookingFeedback) {
                    bookingFeedback.textContent = availability?.message || fallbackMessage;
                }

                if (bookingSubmit) {
                    const canContinue = availability?.stay_available ?? false;
                    bookingSubmit.disabled = !canContinue;
                    bookingSubmit.textContent = canContinue
                        ? 'Continue to booking form'
                        : 'Unavailable for selected dates';
                }
            };

            const setFallbackPricing = (message = 'Select valid dates to preview.') => {
                if (headlineRate) {
                    headlineRate.textContent = formatCurrency(baseNightlyRate);
                }

                if (priceCaption) {
                    priceCaption.textContent = 'per night';
                }

                if (baseRateWrap) {
                    baseRateWrap.classList.add('d-none');
                }

                if (stayValue) {
                    stayValue.textContent = 'Select dates below';
                }

                if (totalValue) {
                    totalValue.textContent = 'Select dates to preview';
                }

                setAvailabilityState(null, message);
            };

            const setPricingPreview = (pricing, availability) => {
                if (headlineRate) {
                    headlineRate.textContent = formatCurrency(pricing.average_nightly_rate);
                }

                if (priceCaption) {
                    priceCaption.textContent = 'selected-stay average / night';
                }

                if (baseRate) {
                    baseRate.textContent = formatCurrency(pricing.base_nightly_rate);
                }

                if (baseRateWrap) {
                    baseRateWrap.classList.toggle('d-none', !pricing.has_date_discount);
                }

                if (discountNote) {
                    discountNote.textContent = pricing.has_date_discount
                        ? `Date discount on ${pricing.discounted_nights} night${pricing.discounted_nights === 1 ? '' : 's'} · Save ${formatCurrency(pricing.discount_amount)}`
                        : '';
                }

                if (stayValue) {
                    const stayStart = parseInputDate(pricing.check_in);
                    const stayEnd = parseInputDate(pricing.check_out);
                    stayValue.textContent = stayStart && stayEnd
                        ? `${dateFormatter.format(stayStart)} - ${dateFormatter.format(stayEnd)} (${pricing.nights} night${pricing.nights === 1 ? '' : 's'})`
                        : 'Select dates below';
                }

                if (totalValue) {
                    totalValue.textContent = formatCurrency(pricing.total);
                }

                setAvailabilityState(availability, 'Select valid dates to preview.');
            };

            const refreshPricingPreview = async () => {
                const checkIn = parseInputDate(checkInInput.value);
                const checkOut = parseInputDate(checkOutInput.value);

                if (!checkIn || !checkOut || checkOut <= checkIn) {
                    setFallbackPricing('Select a valid check-in and check-out date range.');
                    return;
                }

                try {
                    const previewUrl = new URL(form.dataset.previewUrl, window.location.origin);
                    previewUrl.searchParams.set('check_in', checkInInput.value);
                    previewUrl.searchParams.set('check_out', checkOutInput.value);

                    const response = await fetch(previewUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => null);

                    if (!response.ok || !payload?.pricing) {
                        setFallbackPricing(payload?.message || 'Unable to load live room pricing right now.');
                        return;
                    }

                    setPricingPreview(payload.pricing, payload.availability);
                } catch (error) {
                    setFallbackPricing('Unable to load live room pricing right now.');
                }
            };

            checkInInput.addEventListener('change', () => {
                applyDateRules();
                refreshPricingPreview();
            });
            checkOutInput.addEventListener('change', () => {
                applyDateRules();
                refreshPricingPreview();
            });

            applyDateRules();
            refreshPricingPreview();
        })();
    </script>
@endpush

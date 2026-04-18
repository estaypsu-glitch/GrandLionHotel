@extends('layouts.app')

@section('title', 'Create Booking')

@push('head')
    <style>
        .booking-summary-card {
            border-radius: 22px;
        }
        .booking-summary-image {
            display: block;
            width: 100%;
            height: clamp(220px, 26vw, 300px);
            object-fit: cover;
        }
        .booking-summary-body {
            padding: 1rem 1.25rem 1.25rem;
        }
        .booking-summary-price {
            font-size: clamp(1.45rem, 1.1vw + 1rem, 1.9rem);
        }
        .booking-stepper {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.55rem;
            margin-bottom: 1rem;
        }
        .booking-step {
            border-radius: 14px;
            border: 1px solid #e4d8c6;
            background: #fff;
            padding: 0.55rem 0.72rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #425066;
        }
        .booking-step.active {
            border-color: rgba(184, 146, 84, 0.55);
            background: linear-gradient(135deg, rgba(184, 146, 84, 0.13) 0%, rgba(255, 255, 255, 0.96) 100%);
            color: #2f3d53;
        }
        .booking-estimate {
            border-radius: 16px;
            border: 1px solid #e7dccb;
            background: linear-gradient(180deg, #fff 0%, #fbf5ea 100%);
            padding: 0.85rem 0.9rem;
            margin-top: 0.9rem;
        }
        .booking-estimate-row {
            display: flex;
            justify-content: space-between;
            gap: 0.65rem;
            font-size: 0.86rem;
            margin-bottom: 0.34rem;
            color: #4a5568;
        }
        .booking-estimate-row strong {
            color: #111827;
        }
        .booking-estimate-total {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e2d4bf;
            font-size: 1rem;
            font-weight: 800;
            color: #182235;
        }
        .booking-page-alert {
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            scroll-margin-top: 7rem;
        }
        .booking-page-alert.is-visible {
            animation: booking-alert-in 0.28s ease-out;
        }
        @keyframes booking-alert-in {
            0% {
                transform: translateY(-8px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }
        @media (min-width: 992px) {
            .booking-summary-wrap {
                align-self: flex-start;
                position: sticky;
                top: 5.75rem;
                height: fit-content;
            }
        }
        @media (max-width: 767.98px) {
            .booking-stepper {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $nameParts = preg_split('/\s+/', trim($user->name ?? ''), 2);
        $defaultFirstName = $nameParts[0] ?? '';
        $defaultLastName = $nameParts[1] ?? '';
        $provinces = config('philippines.provinces', []);

        $prefill = $prefill ?? [
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guests' => 1,
            'adults' => 1,
            'kids' => 0,
            'minimum_check_in' => now()->toDateString(),
            'minimum_check_out' => now()->addDay()->toDateString(),
            'has_date_selection' => false,
            'date_selection_valid' => false,
            'unavailable_for_selected_dates' => false,
            'availability_message' => null,
        ];

        $initialCheckIn = old('check_in', $prefill['check_in']);
        $initialCheckOut = old('check_out', $prefill['check_out']);
        $initialGuests = max(1, (int) old('guests', $prefill['guests']));
        $initialAdults = max(1, (int) old('adults', $prefill['adults']));
        $initialKids = max(0, (int) old('kids', $prefill['kids']));

        $minimumCheckIn = $prefill['minimum_check_in'];
        $minimumCheckOut = $prefill['minimum_check_out'];
        if (filled($initialCheckIn)) {
            try {
                $minimumCheckOut = \Carbon\Carbon::parse($initialCheckIn)->addDay()->toDateString();
            } catch (\Throwable) {
                $minimumCheckOut = $prefill['minimum_check_out'];
            }
        }
    @endphp

    <div class="row g-4">
        <div class="col-lg-4 booking-summary-wrap">
            <aside class="soft-card overflow-hidden booking-summary-card">
                <img src="{{ $room->image_url }}" alt="{{ $room->name }}" class="booking-summary-image">
                <div class="booking-summary-body">
                    <h2 class="h5 mb-1">{{ $room->name }}</h2>
                    <p class="hotel-meta mb-2">
                        {{ $room->type }}
                        @if(filled($room->view_type))
                            &middot; {{ $room->view_type }}
                        @endif
                        &middot; Up to {{ $room->capacity }} guests
                    </p>
                    <div class="price-tag booking-summary-price">&#8369;{{ number_format($room->price_per_night, 2) }}</div>
                    <small class="text-secondary">per night</small>

                    <div class="booking-estimate">
                        <p class="ta-eyebrow mb-2">Live Estimate</p>
                        <div class="booking-estimate-row"><span>Stay</span><strong id="summary_stay">--</strong></div>
                        <div class="booking-estimate-row"><span id="summary_units_label">Nights</span><strong id="summary_units">--</strong></div>
                        <div class="booking-estimate-row"><span>Rate</span><strong id="summary_rate">--</strong></div>
                        <div class="booking-estimate-row"><span>Guests</span><strong id="summary_guests">{{ $initialGuests }}</strong></div>
                        <div class="booking-estimate-row"><span>Discount</span><strong id="summary_discount">None selected</strong></div>
                        <div class="booking-estimate-row booking-estimate-total mb-0"><span>Estimated total</span><strong id="summary_total">&#8369;{{ number_format($room->price_per_night, 2) }}</strong></div>
                        <small class="text-secondary d-block mt-2">Estimate only. Final amount is based on nights and selected discounts.</small>
                    </div>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <section class="soft-card p-4 p-lg-5">
                <div class="booking-stepper">
                    <div class="booking-step active">1. Guest details</div>
                    <div class="booking-step active">2. Stay schedule</div>
                    <div class="booking-step">3. Staff confirmation</div>
                </div>

                <p class="ta-eyebrow mb-1">Reservation Form</p>
                <h1 class="h3 mb-2">Hotel Reservation Form</h1>
                <p class="text-secondary mb-4">Complete the form below. Your booking request will be reviewed by staff before payment.</p>

                @if(!empty($prefill['availability_message']))
                    <div id="booking_prefill_feedback" class="alert booking-page-alert {{ $prefill['unavailable_for_selected_dates'] ? 'alert-warning' : 'alert-info' }}" role="alert" tabindex="-1">
                        {{ $prefill['availability_message'] }}
                    </div>
                @endif

                <div id="booking_ajax_feedback" class="alert alert-danger booking-page-alert d-none" role="alert" tabindex="-1" aria-live="assertive"></div>

                <form id="booking_form" method="POST" action="{{ route('bookings.store') }}" class="row g-3" enctype="multipart/form-data" data-nightly-rate="{{ number_format((float) $room->price_per_night, 2, '.', '') }}">
                    @csrf
                    <input type="hidden" name="room_id" value="{{ $room->id }}">
                    <input type="hidden" id="guests_input" name="guests" value="{{ $initialGuests }}">

                    <div class="col-12 pt-1">
                        <h2 class="h5 mb-1">Guest Information</h2>
                        <p class="small text-secondary mb-0">Add the details for this reservation assignment.</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">First name</label>
                        <input type="text" class="form-control" name="first_name" value="{{ old('first_name', $defaultFirstName) }}" maxlength="80">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Last name</label>
                        <input type="text" class="form-control" name="last_name" value="{{ old('last_name', $defaultLastName) }}" maxlength="80">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Street address</label>
                        <input type="text" class="form-control" name="street_address" value="{{ old('street_address', $user->address_line) }}" maxlength="255">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Street address line 2</label>
                        <input type="text" class="form-control" name="street_address_line_2" value="{{ old('street_address_line_2') }}" maxlength="255" placeholder="Optional">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="guest_city" value="{{ old('guest_city', $user->city) }}" maxlength="120">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">State / Province</label>
                        <input type="text" list="province-list" class="form-control" name="state_province" value="{{ old('state_province', $user->province) }}" maxlength="120" autocomplete="off" placeholder="Start typing to search province">
                        <datalist id="province-list">
                            @foreach($provinces as $province)
                                <option value="{{ $province }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Postal / Zip code</label>
                        <input type="text" class="form-control" name="postal_code" value="{{ old('postal_code') }}" maxlength="40">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone number</label>
                        <input type="text" class="form-control" name="contact_phone" value="{{ old('contact_phone', $user->phone) }}" maxlength="30" placeholder="+63...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" name="contact_email" value="{{ old('contact_email', $user->email) }}" maxlength="255">
                    </div>

                    <div class="col-12 pt-2">
                        <h2 class="h5 mb-1">Stay Schedule</h2>
                        <p class="small text-secondary mb-0">Set your stay dates</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Arrival date</label>
                        <input type="date" class="form-control" id="check_in_input" name="check_in" required min="{{ $minimumCheckIn }}" value="{{ $initialCheckIn }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Departure date</label>
                        <input type="date" class="form-control" id="check_out_input" name="check_out" required min="{{ $minimumCheckOut }}" value="{{ $initialCheckOut }}">
                    </div>
                    <div class="col-12" id="nightly_time_policy_note">
                    </div>
                    <div class="col-12 pt-2">
                        <h2 class="h5 mb-1">Guests and Payment</h2>
                        <p class="small text-secondary mb-0">Split guest count and choose a preferred payment method.</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Number of adults</label>
                        <input type="number" class="form-control" id="adults_input" name="adults" min="1" max="{{ $room->capacity }}" required value="{{ $initialAdults }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Number of kids (if there are any)</label>
                        <input type="number" class="form-control" id="kids_input" name="kids" min="0" max="{{ $room->capacity }}" value="{{ $initialKids }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Preferred payment method</label>
                        <select class="form-select" name="payment_preference">
                            <option value="">Select one (optional)</option>
                            <option value="cash" @selected(old('payment_preference') === 'cash')>Cash</option>
                            <option value="bank_transfer" @selected(old('payment_preference') === 'bank_transfer')>Bank Transfer</option>
                            <option value="gcash" @selected(old('payment_preference') === 'gcash')>GCash</option>
                            <option value="paymaya" @selected(old('payment_preference') === 'paymaya')>PayMaya</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Discount Type</label>
                        <select class="form-select" name="discount_type" id="discount_type_select">
                            <option value="none" @selected(old('discount_type', 'none') === 'none')>None</option>
                            <option value="pwd" @selected(old('discount_type') === 'pwd')>PWD (20%)</option>
                            <option value="senior" @selected(old('discount_type') === 'senior')>Senior (20%)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Discount ID</label>
                        <input type="text" class="form-control" name="discount_id" id="discount_id_input" maxlength="80" value="{{ old('discount_id') }}" placeholder="PWD/Senior ID number">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Upload Discount ID Photo</label>
                        <input type="file" class="form-control" name="discount_id_photo" id="discount_id_photo_input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small class="text-secondary">Required for PWD/Senior discount.</small>
                    </div>

                    <div class="col-12 d-flex align-items-end">
                        <small id="guest_capacity_note" class="text-secondary">Total guests: <strong id="guest_total">{{ $initialGuests }}</strong> / {{ $room->capacity }}</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Special request (optional)</label>
                        <textarea class="form-control" name="notes" rows="3" maxlength="500" placeholder="Add any special request for your stay">{{ old('notes') }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-light border mb-0 small">
                            Booking status starts as <strong>pending</strong>. Staff will confirm your request before payment checkout is enabled.
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('rooms.show', $room) }}" class="btn btn-ta-outline">Back to room</a>
                        <button type="submit" class="btn btn-ta">Submit booking request</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('booking_form');
            const adultsInput = document.getElementById('adults_input');
            const kidsInput = document.getElementById('kids_input');
            const guestsInput = document.getElementById('guests_input');
            const guestTotal = document.getElementById('guest_total');
            const guestCapacityNote = document.getElementById('guest_capacity_note');
            const checkInInput = document.getElementById('check_in_input');
            const checkOutInput = document.getElementById('check_out_input');
            const ajaxFeedback = document.getElementById('booking_ajax_feedback');
            const prefillFeedback = document.getElementById('booking_prefill_feedback');
            const discountTypeSelect = document.getElementById('discount_type_select');
            const discountIdInput = document.getElementById('discount_id_input');
            const discountIdPhotoInput = document.getElementById('discount_id_photo_input');
            const roomCapacity = {{ (int) $room->capacity }};
            const nightlyRate = Number.parseFloat(form?.dataset.nightlyRate || '0') || 0;

            if (!form || !adultsInput || !kidsInput || !guestsInput || !guestTotal || !checkInInput || !checkOutInput) {
                return;
            }

            const summaryStay = document.getElementById('summary_stay');
            const summaryUnitsLabel = document.getElementById('summary_units_label');
            const summaryUnits = document.getElementById('summary_units');
            const summaryRate = document.getElementById('summary_rate');
            const summaryGuests = document.getElementById('summary_guests');
            const summaryDiscount = document.getElementById('summary_discount');
            const summaryTotal = document.getElementById('summary_total');

            const formatCurrency = (value) => new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                maximumFractionDigits: 2,
            }).format(Math.max(0, value));

            const parseDate = (value) => {
                if (!value) {
                    return null;
                }
                const parsed = new Date(`${value}T00:00:00`);
                return Number.isNaN(parsed.getTime()) ? null : parsed;
            };

            const formatInputDate = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            const nightsBetween = (start, end) => {
                const startDate = parseDate(start);
                const endDate = parseDate(end);
                if (!startDate || !endDate) {
                    return 0;
                }

                const diff = Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));
                return diff > 0 ? diff : 0;
            };

            const scrollToElement = (element) => {
                if (!element) {
                    return;
                }

                const top = window.scrollY + element.getBoundingClientRect().top - 110;
                window.scrollTo({
                    top: Math.max(0, top),
                    behavior: 'smooth',
                });
            };

            const focusElement = (element) => {
                if (!element || typeof element.focus !== 'function') {
                    return;
                }

                window.setTimeout(() => {
                    element.focus({ preventScroll: true });
                }, 180);
            };

            const updateDateRules = () => {
                const checkInDate = parseDate(checkInInput.value);
                if (!checkInDate) {
                    return;
                }

                const minCheckoutDate = new Date(checkInDate);
                minCheckoutDate.setDate(minCheckoutDate.getDate() + 1);
                const minCheckoutValue = formatInputDate(minCheckoutDate);
                checkOutInput.min = minCheckoutValue;

                if (!checkOutInput.value || checkOutInput.value <= checkInInput.value) {
                    checkOutInput.value = minCheckoutValue;
                }
            };

            const updateGuestCount = () => {
                let adults = Math.max(1, Number.parseInt(adultsInput.value || '1', 10) || 1);
                let kids = Math.max(0, Number.parseInt(kidsInput.value || '0', 10) || 0);

                if (adults > roomCapacity) {
                    adults = roomCapacity;
                }

                let total = adults + kids;
                if (total > roomCapacity) {
                    kids = Math.max(0, roomCapacity - adults);
                    total = adults + kids;
                }

                adultsInput.value = adults;
                kidsInput.value = kids;

                guestsInput.value = total;
                guestTotal.textContent = total;
                summaryGuests.textContent = `${total} guest${total === 1 ? '' : 's'}`;

                if (guestCapacityNote) {
                    guestCapacityNote.classList.toggle('text-danger', total >= roomCapacity);
                    guestCapacityNote.classList.toggle('text-secondary', total < roomCapacity);
                }
            };

            const updateEstimate = () => {
                const checkInDate = parseDate(checkInInput.value);
                const checkOutDate = parseDate(checkOutInput.value);
                const nights = nightsBetween(checkInInput.value, checkOutInput.value);
                const total = nights > 0 ? nights * nightlyRate : 0;

                if (summaryStay) {
                    if (checkInDate && checkOutDate) {
                        const formatter = new Intl.DateTimeFormat('en-PH', { month: 'short', day: '2-digit', year: 'numeric' });
                        summaryStay.textContent = `${formatter.format(checkInDate)} - ${formatter.format(checkOutDate)}`;
                    } else {
                        summaryStay.textContent = '--';
                    }
                }

                if (summaryUnits) {
                    summaryUnits.textContent = nights > 0 ? `${nights} night${nights === 1 ? '' : 's'}` : '--';
                }

                if (summaryRate) {
                    summaryRate.textContent = nightlyRate > 0 ? `${formatCurrency(nightlyRate)} / night` : '--';
                }

                if (summaryUnitsLabel) {
                    summaryUnitsLabel.textContent = 'Nights';
                }

                if (summaryTotal) {
                    summaryTotal.textContent = formatCurrency(total);
                }
            };

            const clearFormErrors = () => {
                form.querySelectorAll('.is-invalid').forEach((input) => input.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback.dynamic').forEach((node) => node.remove());
            };

            const showAlert = (message) => {
                if (!ajaxFeedback) {
                    return;
                }

                ajaxFeedback.textContent = message;
                ajaxFeedback.classList.remove('d-none');
                ajaxFeedback.classList.add('is-visible');
                scrollToElement(ajaxFeedback);
                focusElement(ajaxFeedback);

                window.setTimeout(() => {
                    ajaxFeedback.classList.remove('is-visible');
                }, 700);
            };

            const clearAlert = () => {
                if (!ajaxFeedback) {
                    return;
                }

                ajaxFeedback.textContent = '';
                ajaxFeedback.classList.add('d-none');
                ajaxFeedback.classList.remove('is-visible');
            };

            const setFieldError = (field, messages) => {
                const input = form.querySelector(`[name="${field}"]`);
                const primaryMessage = Array.isArray(messages) ? messages[0] : messages;

                if (!input || input.type === 'hidden') {
                    if (primaryMessage) {
                        showAlert(primaryMessage);
                    }

                    return;
                }

                input.classList.add('is-invalid');

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback dynamic';
                feedback.textContent = primaryMessage || 'Invalid value.';
                input.parentElement.appendChild(feedback);
            };

            const showFirstErrorInView = () => {
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    scrollToElement(firstInvalid);
                    focusElement(firstInvalid);
                    return;
                }

                if (ajaxFeedback && !ajaxFeedback.classList.contains('d-none')) {
                    scrollToElement(ajaxFeedback);
                    focusElement(ajaxFeedback);
                }
            };

            const updateDiscountState = () => {
                if (!discountTypeSelect || !discountIdInput) {
                    return;
                }

                const requiresId = discountTypeSelect.value === 'pwd' || discountTypeSelect.value === 'senior';
                discountIdInput.required = requiresId;
                discountIdInput.disabled = !requiresId;
                if (discountIdPhotoInput) {
                    discountIdPhotoInput.required = requiresId;
                    discountIdPhotoInput.disabled = !requiresId;
                }

                if (summaryDiscount) {
                    summaryDiscount.textContent = requiresId
                        ? `${discountTypeSelect.value.toUpperCase()} selected (subject to verification)`
                        : 'None selected';
                }

                if (!requiresId) {
                    discountIdInput.value = '';
                    if (discountIdPhotoInput) {
                        discountIdPhotoInput.value = '';
                    }
                }
            };

            const syncAll = () => {
                updateDateRules();
                updateGuestCount();
                updateEstimate();
                updateDiscountState();
            };

            adultsInput.addEventListener('input', () => {
                updateGuestCount();
                updateEstimate();
            });
            kidsInput.addEventListener('input', () => {
                updateGuestCount();
                updateEstimate();
            });
            checkInInput.addEventListener('change', () => {
                updateDateRules();
                updateEstimate();
            });
            checkOutInput.addEventListener('change', updateEstimate);
            discountTypeSelect?.addEventListener('change', updateDiscountState);

            syncAll();

            if (prefillFeedback && prefillFeedback.classList.contains('alert-warning')) {
                prefillFeedback.classList.add('is-visible');
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                syncAll();
                clearFormErrors();
                clearAlert();

                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonText = submitButton ? submitButton.textContent : '';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Submitting request...';
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const isJson = (response.headers.get('content-type') || '').includes('application/json');
                    const payload = isJson ? await response.json() : null;

                    if (response.ok) {
                        if (payload && payload.redirect) {
                            window.location.href = payload.redirect;
                            return;
                        }

                        if (response.redirected) {
                            window.location.href = response.url;
                            return;
                        }

                        window.location.reload();
                        return;
                    }

                    if (payload && payload.redirect && response.status === 422) {
                        window.location.href = payload.redirect;
                        return;
                    }

                    if (response.status === 422 && payload && payload.errors) {
                        let firstErrorMessage = null;

                        Object.entries(payload.errors).forEach(([field, messages]) => {
                            if (!firstErrorMessage) {
                                firstErrorMessage = Array.isArray(messages) ? messages[0] : messages;
                            }
                            setFieldError(field, messages);
                        });

                        if (firstErrorMessage) {
                            showAlert(firstErrorMessage);
                        }

                        showFirstErrorInView();
                        return;
                    }

                    showAlert((payload && payload.message) ? payload.message : 'Unable to submit booking right now. Please try again.');
                } catch (error) {
                    showAlert('Network error. Please check your connection and try again.');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalButtonText || 'Submit booking request';
                    }
                }
            });
        })();
    </script>
@endpush

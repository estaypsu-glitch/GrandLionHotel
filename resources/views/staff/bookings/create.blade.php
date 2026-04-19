@extends('layouts.staff')

@section('title', 'New Walk-in Booking')

@push('head')
    <style>
        .walkin-shell,
        .walkin-summary {
            border-radius: 14px;
            border: 1px solid #d9e1ef;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .walkin-section {
            border: 1px solid #e2e8f3;
            border-radius: 12px;
            background: #fafcff;
            padding: 0.82rem;
        }
        .walkin-section + .walkin-section {
            margin-top: 0.95rem;
        }
        .walkin-section-title {
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.62rem;
        }
        .walkin-summary {
            position: sticky;
            top: 84px;
        }
        .walkin-summary-line {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.9rem;
            padding: 0.48rem 0;
            border-bottom: 1px dashed #dce4f2;
        }
        .walkin-summary-line:last-child {
            border-bottom: 0;
        }
        .walkin-summary-label {
            color: #64748b;
            font-weight: 700;
        }
        .walkin-summary-value {
            font-weight: 800;
            color: #1f2937;
            text-align: right;
        }
        .walkin-note {
            font-size: 0.8rem;
            color: #64748b;
            margin: 0;
        }
    </style>
@endpush

@section('content')
    <section class="mb-4">
        <h1 class="h4 mb-1">New Walk-in Booking</h1>
        <p class="text-secondary mb-0">Create a front-desk booking from the bookings board.</p>
    </section>

    <form method="POST" action="{{ route('staff.bookings.store') }}">
        @csrf

        <div class="row g-4">
            <div class="col-xl-8">
                <section class="walkin-shell p-3 p-lg-4">
                    <div class="walkin-section">
                        <p class="walkin-section-title">Customer Details</p>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" required>
                                @error('customer_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Phone</label>
                                <input type="tel" name="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" value="{{ old('customer_phone') }}" maxlength="30" placeholder="+63..." pattern="[0-9+()\-\s]{7,30}">
                                @error('customer_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Email (optional)</label>
                                <input type="email" name="customer_email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email') }}">
                                @error('customer_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="walkin-section">
                        <p class="walkin-section-title">Booking Details</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Room <span class="text-danger">*</span></label>
                                <select name="room_id" id="walkin_room_id" class="form-select @error('room_id') is-invalid @enderror" required>
                                    <option value="">Select room...</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}" data-capacity="{{ $room->capacity }}" data-nightly-price="{{ $room->price_per_night }}" @selected(old('room_id') == $room->id)>
                                            {{ $room->name }} ({{ $room->type ?? 'Room' }}{{ filled($room->view_type) ? ', '.$room->view_type : '' }} - {{ $room->capacity }} guests)
                                        </option>
                                    @endforeach
                                </select>
                                @error('room_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Check-in <span class="text-danger">*</span></label>
                                <input type="date" id="walkin_check_in" name="check_in" class="form-control @error('check_in') is-invalid @enderror" value="{{ old('check_in') }}" min="{{ now()->format('Y-m-d') }}" required>
                                @error('check_in')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Check-out <span class="text-danger">*</span></label>
                                <input type="date" id="walkin_check_out" name="check_out" class="form-control @error('check_out') is-invalid @enderror" value="{{ old('check_out') }}" min="{{ now()->addDay()->format('Y-m-d') }}" required>
                                @error('check_out')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <small class="text-secondary">
                                    Nightly schedule: check-in is 2:00 PM and check-out is 12:00 PM.
                                </small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Guests <span class="text-danger">*</span></label>
                                <input type="number" id="walkin_guests" name="guests" class="form-control @error('guests') is-invalid @enderror" value="{{ old('guests', 1) }}" min="1" required>
                                @error('guests')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Payment Preference</label>
                                <select name="payment_preference" class="form-select">
                                    <option value="">Select preference...</option>
                                    <option value="cash" @selected(old('payment_preference') == 'cash')>Cash</option>
                                    <option value="instapay" @selected(old('payment_preference') == 'instapay')>InstaPay</option>
                                    <option value="credit_debit_card" @selected(old('payment_preference') == 'credit_debit_card')>Credit/Debit Card</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="walkin-section">
                        <p class="walkin-section-title">Operational Notes</p>
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Special requests, allergies, VIP handling, arrival instructions...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('staff.bookings.index') }}" class="btn btn-staff-outline">Back to Bookings</a>
                        <button type="submit" class="btn btn-staff px-4">Create Booking</button>
                    </div>
                </section>
            </div>

            <div class="col-xl-4">
                <aside class="walkin-summary p-3 p-lg-4">
                    <h2 class="h5 mb-3">Booking Summary</h2>
                    <div class="walkin-summary-line">
                        <span class="walkin-summary-label">Selected Room</span>
                        <span class="walkin-summary-value" id="summary_room">-</span>
                    </div>
                    <div class="walkin-summary-line">
                        <span class="walkin-summary-label">Guest Capacity</span>
                        <span class="walkin-summary-value" id="summary_capacity">-</span>
                    </div>
                    <div class="walkin-summary-line">
                        <span class="walkin-summary-label" id="summary_rate_label">Nightly Rate</span>
                        <span class="walkin-summary-value" id="summary_rate">-</span>
                    </div>
                    <div class="walkin-summary-line">
                        <span class="walkin-summary-label" id="summary_units_label">Stay Nights</span>
                        <span class="walkin-summary-value" id="summary_units">-</span>
                    </div>
                    <div class="walkin-summary-line">
                        <span class="walkin-summary-label">Estimated Total</span>
                        <span class="walkin-summary-value" id="summary_total">-</span>
                    </div>
                    <p class="walkin-note mt-3">Estimate only. Final amount is based on nightly rate and number of nights.</p>
                </aside>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roomSelect = document.getElementById('walkin_room_id');
        const guestsInput = document.getElementById('walkin_guests');
        const checkInInput = document.getElementById('walkin_check_in');
        const checkOutInput = document.getElementById('walkin_check_out');

        const summaryRoom = document.getElementById('summary_room');
        const summaryCapacity = document.getElementById('summary_capacity');
        const summaryRateLabel = document.getElementById('summary_rate_label');
        const summaryUnitsLabel = document.getElementById('summary_units_label');
        const summaryRate = document.getElementById('summary_rate');
        const summaryUnits = document.getElementById('summary_units');
        const summaryTotal = document.getElementById('summary_total');

        if (!roomSelect || !guestsInput || !checkInInput || !checkOutInput) {
            return;
        }

        const toDateInputValue = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const addDays = (dateValue, days) => {
            const base = new Date(`${dateValue}T00:00:00`);
            base.setDate(base.getDate() + days);
            return toDateInputValue(base);
        };

        const formatMoney = (value) => `PHP ${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        const calculateNights = () => {
            const checkIn = checkInInput.value;
            const checkOut = checkOutInput.value;

            if (!checkIn || !checkOut) {
                return 0;
            }

            const start = new Date(`${checkIn}T00:00:00`);
            const end = new Date(`${checkOut}T00:00:00`);
            const diff = Math.floor((end - start) / (1000 * 60 * 60 * 24));
            return Math.max(0, diff);
        };

        const syncGuestCapacity = () => {
            const selectedOption = roomSelect.options[roomSelect.selectedIndex];
            const capacity = selectedOption ? selectedOption.dataset.capacity : '';

            if (capacity) {
                guestsInput.max = capacity;
                if (parseInt(guestsInput.value, 10) > parseInt(capacity, 10)) {
                    guestsInput.value = capacity;
                }
                return;
            }

            guestsInput.removeAttribute('max');
        };

        const updateSummary = () => {
            const selectedOption = roomSelect.options[roomSelect.selectedIndex];
            const roomLabel = selectedOption && selectedOption.value !== '' ? selectedOption.textContent.trim() : '-';
            const capacity = selectedOption && selectedOption.dataset.capacity ? selectedOption.dataset.capacity : '-';
            const nightlyRate = selectedOption && selectedOption.dataset.nightlyPrice ? Number(selectedOption.dataset.nightlyPrice) : 0;
            const nights = calculateNights();
            const estimatedTotal = nights > 0 ? nights * nightlyRate : 0;

            if (summaryRateLabel) {
                summaryRateLabel.textContent = 'Nightly Rate';
            }
            if (summaryUnitsLabel) {
                summaryUnitsLabel.textContent = 'Stay Nights';
            }
            if (summaryRoom) {
                summaryRoom.textContent = roomLabel;
            }
            if (summaryCapacity) {
                summaryCapacity.textContent = capacity === '-' ? '-' : `${capacity} guests`;
            }
            if (summaryRate) {
                summaryRate.textContent = nightlyRate > 0 ? formatMoney(nightlyRate) : '-';
            }
            if (summaryUnits) {
                summaryUnits.textContent = nights > 0 ? `${nights} night${nights === 1 ? '' : 's'}` : '-';
            }
            if (summaryTotal) {
                summaryTotal.textContent = estimatedTotal > 0 ? formatMoney(estimatedTotal) : '-';
            }
        };

        roomSelect.addEventListener('change', () => {
            syncGuestCapacity();
            updateSummary();
        });

        checkInInput.addEventListener('change', function() {
            if (!this.value) {
                return;
            }

            const minCheckout = addDays(this.value, 1);
            checkOutInput.min = minCheckout;

            if (!checkOutInput.value || checkOutInput.value <= this.value) {
                checkOutInput.value = minCheckout;
            }

            updateSummary();
        });

        checkOutInput.addEventListener('change', updateSummary);

        if (checkInInput.value) {
            checkOutInput.min = addDays(checkInInput.value, 1);
        }

        syncGuestCapacity();
        updateSummary();
    });
</script>
@endpush

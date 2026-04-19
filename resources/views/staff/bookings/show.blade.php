@extends('layouts.staff')

@section('title', 'Booking Details')

@push('head')
    <style>
        .booking-shell,
        .booking-side-shell {
            border-radius: 14px;
            border: 1px solid #d9e1ef;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .booking-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 0.75rem;
        }
        .booking-info-item {
            border: 1px solid #e2e8f3;
            border-radius: 12px;
            background: #fafcff;
            padding: 0.64rem 0.7rem;
        }
        .booking-info-label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.22rem;
        }
        .booking-info-value {
            font-size: 0.92rem;
            color: #1f2937;
            font-weight: 700;
            word-break: break-word;
        }
        .booking-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .booking-actions form {
            margin: 0;
        }
        .booking-actions .btn {
            min-height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.32rem;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1;
            padding: 0.45rem 0.78rem;
        }
        .booking-actions .btn-staff {
            box-shadow: 0 5px 10px rgba(var(--theme-primary-rgb), 0.18);
        }
        .booking-actions .btn-staff:hover {
            box-shadow: 0 8px 14px rgba(var(--theme-secondary-rgb), 0.2);
        }
        .btn-staff-danger {
            border-radius: 10px;
            border: 1px solid rgba(var(--theme-secondary-rgb), 0.52);
            color: var(--theme-secondary);
            background: rgba(var(--theme-secondary-rgb), 0.08);
        }
        .btn-staff-danger:hover,
        .btn-staff-danger:focus {
            border-color: var(--theme-secondary);
            background: var(--theme-secondary);
            color: #fff;
        }
        .booking-note {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.35rem;
        }
        .booking-side-shell {
            position: sticky;
            top: 84px;
        }
        .booking-meta-line {
            display: flex;
            justify-content: space-between;
            gap: 0.65rem;
            padding: 0.45rem 0;
            border-bottom: 1px dashed #dce4f2;
            font-size: 0.9rem;
        }
        .booking-meta-line:last-child {
            border-bottom: 0;
        }
        .booking-meta-label {
            color: #64748b;
            font-weight: 700;
        }
        .booking-meta-value {
            color: #1f2937;
            font-weight: 800;
            text-align: right;
        }
        @media (max-width: 1199.98px) {
            .booking-side-shell {
                position: static;
            }
        }
        .booking-top-chip {
            border-radius: 999px;
            border: 1px solid #d7deec;
            background: #f8fbff;
            color: #334155;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.22rem 0.58rem;
            display: inline-flex;
            align-items: center;
        }
        .booking-log-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 0.9rem;
        }
        .booking-log-card {
            border: 1px solid #dce5f3;
            border-radius: 14px;
            background: #fbfdff;
            padding: 0.95rem;
        }
        .booking-log-time {
            font-size: 1rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.65rem;
        }
    </style>
@endpush

@section('content')
    @php
        $reservationMeta = $booking->reservation_meta ?? [];
        $displayName = $booking->guestName();
        $displayEmail = $booking->guestEmail();
        $displayPhone = $booking->guestPhone();
        $discountProofPath = (string) data_get($reservationMeta, 'discount_id_photo_path', '');
        $discountProofUrl = $discountProofPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($discountProofPath)
            : '';
        $paymentProofPath = trim((string) ($booking->payment?->payment_proof_path ?? ''));
        $paymentProofUrl = $paymentProofPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($paymentProofPath)
            : '';
        $profileAddress = trim(collect([
            $booking->user?->address_line,
            $booking->user?->city,
            $booking->user?->country,
        ])->filter()->implode(', '));
        $guestAddress = collect([
            $reservationMeta['street_address'] ?? null,
            $reservationMeta['street_address_line_2'] ?? null,
            $reservationMeta['guest_city'] ?? null,
            $reservationMeta['state_province'] ?? null,
            $reservationMeta['postal_code'] ?? null,
        ])->filter()->implode(', ');
        $bookingStatusLabel = ucfirst($booking->status);
        $paymentStatusLabel = ucfirst(str_replace('_', ' ', $booking->payment_status));
        $billedUnits = $booking->nights();
        $isOnlineAwaitingVerification = $booking->payment_status === 'pending_verification'
            && \App\Models\Payment::isOnlineMethod((string) ($booking->payment?->method ?? ''));
        $hasPendingRescheduleRequest = $booking->hasPendingRescheduleRequest();
        $hasPendingRoomTransferRequest = $booking->hasPendingRoomTransferRequest();
        $canStaffDirectlyReschedule = $booking->canBeRescheduledByStaff();
        $canStaffTransferRoom = $booking->canBeTransferredByStaff();
        $defaultCheckInTime = old('actual_check_in_at', now()->format('Y-m-d\TH:i'));
        $defaultCheckOutTime = old('actual_check_out_at', now()->format('Y-m-d\TH:i'));
    @endphp

    <section class="mb-4">
        <div class="mb-2">
            <a href="{{ $backUrl }}" class="btn btn-staff-outline btn-sm">
                <i class="bi bi-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>
        <h1 class="h4 mb-1">Booking #{{ $booking->id }}</h1>
        <div class="d-flex flex-wrap gap-2">
            <span class="booking-top-chip">Booking status: {{ $bookingStatusLabel }}</span>
            <span class="booking-top-chip">Payment status: {{ $paymentStatusLabel }}</span>
            <span class="booking-top-chip">Guest: {{ $displayName }}</span>
        </div>
    </section>

    <div class="booking-actions mb-3">
        @if($booking->canBeConfirmedByStaff())
            <form method="POST" action="{{ route('staff.bookings.confirm', $booking) }}" data-confirm="Confirm this booking now?">
                @csrf
                @method('PATCH')
                @if(!empty($returnTo))
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <button type="submit" class="btn btn-staff">
                    <i class="bi bi-check2-circle"></i>
                    <span>Confirm booking</span>
                </button>
            </form>
        @endif

        @if(in_array($booking->status, ['pending', 'confirmed'], true) && is_null($booking->actual_check_in_at))
            <form method="POST" action="{{ route('staff.bookings.cancel', $booking) }}" data-confirm="Cancel this booking?">
                @csrf
                @method('PATCH')
                @if(!empty($returnTo))
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <button type="submit" class="btn btn-staff-danger">
                    <i class="bi bi-x-circle"></i>
                    <span>Cancel booking</span>
                </button>
            </form>
        @endif

        @if($booking->payment_status === 'paid')
            <a href="{{ route('staff.bookings.receipt', $booking) }}" class="btn btn-staff-outline">
                <i class="bi bi-file-earmark-arrow-down"></i>
                <span>Receipt PDF</span>
            </a>
        @endif
    </div>

    <section class="booking-shell p-3 p-lg-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
            <div>
                <h2 class="h5 mb-1">Arrival & Departure Log</h2>
                <p class="booking-note mb-0">Staff records the actual guest arrival and departure here. Customers can only view these timestamps in their booking details.</p>
            </div>
        </div>
        <div class="booking-log-grid">
            <article class="booking-log-card">
                <p class="booking-info-label">Actual Check-In</p>
                <p class="booking-log-time">{{ optional($booking->actual_check_in_at)->format('M d, Y h:i A') ?? 'Not logged yet' }}</p>

                @if($booking->canBeCheckedInByStaff())
                    <form method="POST" action="{{ route('staff.bookings.check-in', $booking) }}" class="row g-3 align-items-end" data-confirm="Save this guest check-in time?">
                        @csrf
                        @method('PATCH')
                        @if(!empty($returnTo))
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        @endif
                        <div class="col-12">
                            <label class="form-label">Arrival date and time</label>
                            <input
                                type="datetime-local"
                                name="actual_check_in_at"
                                class="form-control @error('actual_check_in_at') is-invalid @enderror"
                                value="{{ $defaultCheckInTime }}"
                                max="{{ now()->format('Y-m-d\TH:i') }}"
                                required
                            >
                            @error('actual_check_in_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <button type="submit" class="btn btn-staff w-100">
                                <i class="bi bi-door-open"></i>
                                <span>Save check-in</span>
                            </button>
                        </div>
                    </form>
                @elseif($booking->actual_check_in_at)
                    <p class="booking-note mb-0">This is the staff-recorded arrival time for the guest.</p>
                @else
                    <p class="booking-note mb-0">Check-in becomes available after the booking is confirmed and the guest arrival date has started.</p>
                @endif
            </article>

            <article class="booking-log-card">
                <p class="booking-info-label">Actual Check-Out</p>
                <p class="booking-log-time">{{ optional($booking->actual_check_out_at)->format('M d, Y h:i A') ?? 'Not logged yet' }}</p>

                @if($booking->canBeCheckedOutByStaff() && $booking->payment_status === 'paid')
                    <form method="POST" action="{{ route('staff.bookings.check-out', $booking) }}" class="row g-3 align-items-end" data-confirm="Save this guest check-out time and complete the booking?">
                        @csrf
                        @method('PATCH')
                        @if(!empty($returnTo))
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        @endif
                        <div class="col-12">
                            <label class="form-label">Departure date and time</label>
                            <input
                                type="datetime-local"
                                name="actual_check_out_at"
                                class="form-control @error('actual_check_out_at') is-invalid @enderror"
                                value="{{ $defaultCheckOutTime }}"
                                max="{{ now()->format('Y-m-d\TH:i') }}"
                                required
                            >
                            @error('actual_check_out_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <button type="submit" class="btn btn-staff w-100">
                                <i class="bi bi-door-closed"></i>
                                <span>Save check-out</span>
                            </button>
                        </div>
                    </form>
                @elseif($booking->actual_check_out_at)
                    <p class="booking-note mb-0">This is the staff-recorded departure time for the guest.</p>
                @elseif($booking->canBeCheckedOutByStaff())
                    <p class="booking-note mb-0">Mark the booking payment as paid first before recording the guest check-out.</p>
                @else
                    <p class="booking-note mb-0">Check-out becomes available after the guest has been checked in.</p>
                @endif
            </article>
        </div>
    </section>

    @if($hasPendingRescheduleRequest)
        <section class="booking-shell p-3 p-lg-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
                <div>
                    <h2 class="h5 mb-1">Pending Schedule Change Request</h2>
                    <p class="booking-note mb-0">Customer requested a new stay schedule. Apply it only if the requested dates are still available.</p>
                </div>
            </div>
            <div class="booking-info-grid mb-3">
                <div class="booking-info-item">
                    <p class="booking-info-label">Current Dates</p>
                    <p class="booking-info-value">{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Requested Dates</p>
                    <p class="booking-info-value">{{ $booking->requested_check_in?->format('M d, Y') ?? '-' }} - {{ $booking->requested_check_out?->format('M d, Y') ?? '-' }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Requested At</p>
                    <p class="booking-info-value">{{ optional($booking->reschedule_requested_at)->format('M d, Y h:i A') ?? '-' }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Customer Note</p>
                    <p class="booking-info-value">{{ $booking->reschedule_request_notes ?: '-' }}</p>
                </div>
            </div>
            <div class="booking-actions">
                <form method="POST" action="{{ route('staff.bookings.apply-reschedule-request', $booking) }}" data-confirm="Apply this requested schedule to the booking now?">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-staff">
                        <i class="bi bi-calendar-check"></i>
                        <span>Apply requested schedule</span>
                    </button>
                </form>
                <form method="POST" action="{{ route('staff.bookings.decline-reschedule-request', $booking) }}" data-confirm="Decline and clear this schedule change request?">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-staff-outline">
                        <i class="bi bi-calendar-x"></i>
                        <span>Decline request</span>
                    </button>
                </form>
            </div>
        </section>
    @endif

    @if($hasPendingRoomTransferRequest)
        <section class="booking-shell p-3 p-lg-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
                <div>
                    <h2 class="h5 mb-1">Pending Room Transfer Request</h2>
                    <p class="booking-note mb-0">Customer requested a room change. Review the reason, then use the room transfer section below if you approve.</p>
                </div>
            </div>
            <div class="booking-info-grid mb-3">
                <div class="booking-info-item">
                    <p class="booking-info-label">Current Room</p>
                    <p class="booking-info-value">Room {{ $booking->room->name ?? $booking->room_id }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Requested At</p>
                    <p class="booking-info-value">{{ optional($booking->room_transfer_requested_at)->format('M d, Y h:i A') ?? '-' }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Customer Reason</p>
                    <p class="booking-info-value">{{ $booking->room_transfer_request_reason ?: '-' }}</p>
                </div>
            </div>
            <div class="booking-actions">
                <form method="POST" action="{{ route('staff.bookings.decline-room-transfer-request', $booking) }}" data-confirm="Decline and clear this room transfer request?">
                    @csrf
                    @method('PATCH')
                    @if(!empty($returnTo))
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <button type="submit" class="btn btn-staff-outline">
                        <i class="bi bi-x-circle"></i>
                        <span>Decline request</span>
                    </button>
                </form>
            </div>
        </section>
    @endif

    @if($canStaffDirectlyReschedule)
        <section class="booking-shell p-3 p-lg-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
                <div>
                    <h2 class="h5 mb-1">Direct Staff Reschedule</h2>
                    <p class="booking-note mb-0">Use this when the customer asks for a schedule change in person at the hotel. The system will recheck availability and update the due amount automatically when the booking is not yet paid.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('staff.bookings.reschedule', $booking) }}" class="row g-3" data-confirm="Update this booking schedule now?">
                @csrf
                @method('PATCH')
                <div class="col-md-6">
                    <label class="form-label">New check-in</label>
                    <input
                        type="date"
                        name="check_in"
                        class="form-control @error('check_in') is-invalid @enderror"
                        min="{{ now()->toDateString() }}"
                        value="{{ old('check_in', $booking->check_in->toDateString()) }}"
                        required
                    >
                    @error('check_in')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">New check-out</label>
                    <input
                        type="date"
                        name="check_out"
                        class="form-control @error('check_out') is-invalid @enderror"
                        min="{{ now()->addDay()->toDateString() }}"
                        value="{{ old('check_out', $booking->check_out->toDateString()) }}"
                        required
                    >
                    @error('check_out')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-staff">
                        <i class="bi bi-calendar2-week"></i>
                        <span>Update schedule</span>
                    </button>
                </div>
            </form>
        </section>
    @endif

    @if($canStaffTransferRoom)
        <section class="booking-shell p-3 p-lg-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
                <div>
                    <h2 class="h5 mb-1">Room Transfer</h2>
                    <p class="booking-note mb-0">
                        Use this when the guest asks staff to move them to another room while keeping the same stay dates.
                        @if($transferRequiresSameTotal)
                            Only same-total rooms are listed because this booking already has a submitted or recorded payment.
                        @else
                            If the booking is still unpaid, the amount due will update automatically after the transfer.
                        @endif
                    </p>
                </div>
            </div>

            <div class="booking-info-grid mb-3">
                <div class="booking-info-item">
                    <p class="booking-info-label">Current Room</p>
                    <p class="booking-info-value">{{ $booking->room->name ?? '-' }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Guest Count</p>
                    <p class="booking-info-value">{{ $booking->guests }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Stay Dates</p>
                    <p class="booking-info-value">{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Current Stay Total</p>
                    <p class="booking-info-value">PHP {{ number_format($currentStayTotal, 2) }}</p>
                </div>
            </div>

            @if($transferRooms->isNotEmpty())
                <form method="POST" action="{{ route('staff.bookings.transfer-room', $booking) }}" class="row g-3 align-items-end" data-confirm="Move this booking to the selected room now?">
                    @csrf
                    @method('PATCH')
                    <div class="col-lg-8">
                        <label class="form-label">New room</label>
                        <select name="room_id" class="form-select @error('room_id') is-invalid @enderror" required>
                            <option value="">Select available room...</option>
                            @foreach($transferRooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>
                                    {{ $room->name }} ({{ $room->type ?? 'Room' }}{{ filled($room->view_type) ? ', '.$room->view_type : '' }} - {{ $room->capacity }} guests) - PHP {{ number_format((float) $room->transfer_stay_total, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('room_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-lg-4">
                        <button type="submit" class="btn btn-staff w-100">
                            <i class="bi bi-arrow-left-right"></i>
                            <span>Transfer room</span>
                        </button>
                    </div>
                </form>
            @else
                <p class="booking-note mb-0">
                    No matching rooms are currently available for this booking's dates and guest count.
                </p>
            @endif
        </section>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <section class="booking-shell p-3 p-lg-4 mb-4">
                <h2 class="h5 mb-3">Guest & Stay Information</h2>
                <div class="booking-info-grid">
                    <div class="booking-info-item">
                        <p class="booking-info-label">Guest Name</p>
                        <p class="booking-info-value">{{ $displayName }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Email</p>
                        <p class="booking-info-value">{{ $displayEmail }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Phone</p>
                        <p class="booking-info-value">{{ $displayPhone }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Room</p>
                        <p class="booking-info-value">{{ $booking->room->name ?? '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Stay Dates</p>
                        <p class="booking-info-value">{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Stay Type</p>
                        <p class="booking-info-value">Nightly</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Guests</p>
                        <p class="booking-info-value">{{ $booking->guests }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Nights</p>
                        <p class="booking-info-value">{{ $billedUnits }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Actual Check-In</p>
                        <p class="booking-info-value">{{ optional($booking->actual_check_in_at)->format('M d, Y h:i A') ?? '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Actual Check-Out</p>
                        <p class="booking-info-value">{{ optional($booking->actual_check_out_at)->format('M d, Y h:i A') ?? '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Assigned Staff</p>
                        <p class="booking-info-value">{{ $booking->assignedStaff->name ?? '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Profile Address</p>
                        <p class="booking-info-value">{{ $profileAddress ?: '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Reservation Address</p>
                        <p class="booking-info-value">{{ $guestAddress !== '' ? $guestAddress : '-' }}</p>
                    </div>
                    @if(!empty($reservationMeta['payment_preference']))
                        <div class="booking-info-item">
                            <p class="booking-info-label">Preferred Payment</p>
                            <p class="booking-info-value">{{ ucfirst(str_replace('_', ' ', $reservationMeta['payment_preference'])) }}</p>
                        </div>
                    @endif
                    @if(!empty($reservationMeta['discount_type']) && $reservationMeta['discount_type'] !== 'none')
                        <div class="booking-info-item">
                            <p class="booking-info-label">Requested Discount</p>
                            <p class="booking-info-value">{{ strtoupper((string) $reservationMeta['discount_type']) }} (20%)</p>
                        </div>
                    @endif
                </div>

                @if($booking->notes)
                    <p class="booking-note"><strong>Guest Notes:</strong> {{ $booking->notes }}</p>
                @endif
            </section>

            <section class="booking-shell p-3 p-lg-4 mb-4">
                <h2 class="h5 mb-3">Internal Staff Notes</h2>
                <form method="POST" action="{{ route('staff.bookings.staff-notes', $booking) }}" class="row g-3">
                    @csrf
                    @method('PATCH')
                    <div class="col-12">
                        <label class="form-label">Visible to staff/admin only</label>
                        <textarea
                            name="staff_notes"
                            class="form-control"
                            rows="4"
                            placeholder="Add check-in reminders, payment follow-up, housekeeping coordination, or special handling notes."
                        >{{ old('staff_notes', $booking->staff_notes) }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-staff w-100">Save notes</button>
                    </div>
                </form>
            </section>

            <section class="booking-shell p-3 p-lg-4">
                <h2 class="h5 mb-2">Manual Status Update</h2>
                <form method="POST" action="{{ route('staff.bookings.update-status', $booking) }}" class="row g-3 align-items-end" data-confirm="Apply this booking status update?">
                    @csrf
                    @method('PATCH')
                    @if(!empty($returnTo))
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            @foreach(['pending', 'confirmed', 'cancelled', 'completed'] as $status)
                                <option value="{{ $status }}" {{ $booking->status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-staff w-100">Update</button>
                    </div>
                </form>
            </section>
        </div>
        <div class="col-xl-4">
            <aside class="booking-side-shell p-3 p-lg-4">
                <h2 class="h5 mb-3">Payment Desk</h2>

                @if($isOnlineAwaitingVerification)
                    <div class="alert alert-info small mb-3">
                        Customer submitted online payment proof. Verify the transfer using the uploaded screenshot and reference number, then approve or reject.
                    </div>
                    <div class="d-grid gap-2 mb-4">
                        <form method="POST" action="{{ route('staff.bookings.approve-online-payment', $booking) }}" data-confirm="Approve this online payment submission?">
                            @csrf
                            @method('PATCH')
                            @if(!empty($returnTo))
                                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            @endif
                            <button type="submit" class="btn btn-staff w-100">Approve Online Payment</button>
                        </form>
                        <form method="POST" action="{{ route('staff.bookings.reject-online-payment', $booking) }}" data-confirm="Reject this online payment submission?">
                            @csrf
                            @method('PATCH')
                            @if(!empty($returnTo))
                                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            @endif
                            <button type="submit" class="btn btn-staff-outline w-100">Reject Submission</button>
                        </form>
                    </div>
                @endif

                @if($booking->payment_status === 'unpaid' && in_array($booking->status, ['confirmed'], true))
                    <form method="POST" action="{{ route('staff.bookings.record-payment', $booking) }}" class="row g-3 align-items-end mb-4" data-confirm="Record this payment now?">
                        @csrf
                        @method('PATCH')
                        @if(!empty($returnTo))
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        @endif
                        <div class="col-12">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="method" required>
                                <option value="cash" @selected(old('method', 'cash') === 'cash')>Cash</option>
                                <option value="instapay" @selected(old('method') === 'instapay')>InstaPay</option>
                                <option value="credit_debit_card" @selected(old('method') === 'credit_debit_card')>Credit/Debit Card</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Discount</label>
                            <select class="form-select" name="discount_type">
                                <option value="none" @selected(old('discount_type', data_get($booking->reservation_meta, 'discount_type', 'none')) === 'none')>None</option>
                                <option value="pwd" @selected(old('discount_type', data_get($booking->reservation_meta, 'discount_type')) === 'pwd')>PWD (20%)</option>
                                <option value="senior" @selected(old('discount_type', data_get($booking->reservation_meta, 'discount_type')) === 'senior')>Senior (20%)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Discount ID Number</label>
                            <input type="text" name="discount_id" class="form-control" maxlength="80" placeholder="PWD/Senior ID" value="{{ old('discount_id', data_get($booking->reservation_meta, 'discount_id')) }}">
                            @if($discountProofUrl !== '')
                                <small class="text-secondary">Uploaded proof available: <a href="{{ $discountProofUrl }}" target="_blank" rel="noopener">View photo</a></small>
                            @endif
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-staff w-100">Record Payment</button>
                        </div>
                    </form>
                @endif

                <div class="booking-meta-line">
                    <span class="booking-meta-label">Current Payment Status</span>
                    <span class="booking-meta-value">{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</span>
                </div>

                @if($booking->payment)
                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Method</span>
                        <span class="booking-meta-value">{{ \App\Models\Payment::methodLabel($booking->payment->method) }}</span>
                    </div>
                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Status</span>
                        <span class="booking-meta-value">{{ ucfirst($booking->payment->status) }}</span>
                    </div>
                    @if(filled($booking->payment->customer_reference))
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Customer Ref No.</span>
                            <span class="booking-meta-value">{{ $booking->payment->customer_reference }}</span>
                        </div>
                    @endif
                    @if(filled($booking->payment->qr_reference))
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">QR Reference</span>
                            <span class="booking-meta-value">{{ $booking->payment->qr_reference }}</span>
                        </div>
                    @endif
                    @if($paymentProofUrl !== '')
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Uploaded Proof</span>
                            <span class="booking-meta-value"><a href="{{ $paymentProofUrl }}" target="_blank" rel="noopener">View screenshot</a></span>
                        </div>
                    @endif
                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Amount</span>
                        <span class="booking-meta-value">PHP {{ number_format($booking->payment->amount, 2) }}</span>
                    </div>

                    @if(filled(data_get($reservationMeta, 'discount_type')) && (float) ($booking->payment->discount_amount ?? 0) > 0)
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Original Amount</span>
                            <span class="booking-meta-value">PHP {{ number_format((float) ($booking->payment->original_amount ?? $booking->total_price), 2) }}</span>
                        </div>
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Discount</span>
                            <span class="booking-meta-value">{{ strtoupper((string) data_get($reservationMeta, 'discount_type')) }} ({{ number_format((float) ($booking->payment->discount_rate ?? 0) * 100, 0) }}%)</span>
                        </div>
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Discount Amount</span>
                            <span class="booking-meta-value">PHP {{ number_format((float) ($booking->payment->discount_amount ?? 0), 2) }}</span>
                        </div>
                    @endif

                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Paid At</span>
                        <span class="booking-meta-value">{{ optional($booking->payment->paid_at)->format('M d, Y h:i A') ?? '-' }}</span>
                    </div>
                    @if($booking->payment->verified_at)
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Verified At</span>
                            <span class="booking-meta-value">{{ optional($booking->payment->verified_at)->format('M d, Y h:i A') ?? '-' }}</span>
                        </div>
                    @endif
                    @if($booking->payment->verifiedByStaff)
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Verified By</span>
                            <span class="booking-meta-value">{{ $booking->payment->verifiedByStaff->name }}</span>
                        </div>
                    @endif
                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Transaction Ref</span>
                        <span class="booking-meta-value">{{ $booking->payment->transaction_reference ?? '-' }}</span>
                    </div>
                @else
                    <p class="text-secondary mb-0">No payment record yet.</p>
                @endif
            </aside>
        </div>
    </div>
@endsection

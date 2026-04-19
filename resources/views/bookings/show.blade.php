@extends('layouts.app')

@section('title', 'Booking Details')

@push('head')
    <style>
        .booking-flow {
            border-radius: 18px;
            border: 1px solid var(--line);
            background: #fff;
            padding: 1rem;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }
        .booking-flow-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.55rem;
        }
        .booking-flow-step {
            border-radius: 12px;
            border: 1px solid #e7dccb;
            background: #fff;
            padding: 0.62rem 0.7rem;
            min-height: 72px;
        }
        .booking-flow-step .label {
            font-size: 0.74rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 800;
            margin-bottom: 0.18rem;
        }
        .booking-flow-step .status {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1f2937;
        }
        .booking-flow-step.is-complete {
            border-color: rgba(6, 118, 71, 0.35);
            background: linear-gradient(180deg, rgba(241, 250, 245, 0.96) 0%, rgba(236, 247, 240, 0.96) 100%);
        }
        .booking-flow-step.is-current {
            border-color: rgba(184, 146, 84, 0.45);
            background: linear-gradient(180deg, rgba(250, 245, 235, 0.96) 0%, rgba(255, 255, 255, 0.98) 100%);
        }
        .booking-flow-step.is-cancelled {
            border-color: rgba(180, 35, 24, 0.35);
            background: linear-gradient(180deg, rgba(254, 244, 244, 0.96) 0%, rgba(255, 249, 249, 0.98) 100%);
        }
        .booking-next-card {
            border-radius: 16px;
            border: 1px dashed rgba(184, 146, 84, 0.52);
            background: rgba(184, 146, 84, 0.08);
            padding: 0.8rem 0.85rem;
        }
        @media (max-width: 991.98px) {
            .booking-flow-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 575.98px) {
            .booking-flow-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $reservationMeta = $booking->reservation_meta ?? [];
        $discountProofPath = (string) data_get($reservationMeta, 'discount_id_photo_path', '');
        $discountProofUrl = $discountProofPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($discountProofPath)
            : '';
        $paymentProofPath = trim((string) ($booking->payment?->payment_proof_path ?? ''));
        $paymentProofUrl = $paymentProofPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($paymentProofPath)
            : '';

        $isCancelled = $booking->status === 'cancelled';
        $isRequested = true;
        $isConfirmed = in_array($booking->status, ['confirmed', 'completed'], true);
        $canRequestReschedule = $booking->canRequestReschedule();
        $hasPendingRescheduleRequest = $booking->hasPendingRescheduleRequest();
        $canRequestRoomTransfer = $booking->canRequestRoomTransfer();
        $hasPendingRoomTransferRequest = $booking->hasPendingRoomTransferRequest();
        $isPaid = $booking->payment_status === 'paid';
        $isCashAwaitingVerification = $booking->status === 'confirmed'
            && $booking->payment_status !== 'paid'
            && strtolower((string) ($booking->payment?->method ?? '')) === 'cash';
        $isOnlineAwaitingVerification = $booking->status === 'confirmed'
            && $booking->payment_status === 'pending_verification'
            && \App\Models\Payment::isOnlineMethod((string) ($booking->payment?->method ?? ''));
        $isCompleted = $booking->status === 'completed';
        $billedUnits = $booking->nights();

        $nextAction = match (true) {
            $isCancelled => 'This reservation has been cancelled. If you still plan to stay, create a new booking.',
            $booking->status === 'pending' => 'Wait for staff confirmation. Payment becomes available right after approval.',
            $isOnlineAwaitingVerification => 'Your online payment proof was submitted. Please wait for staff to verify your transfer.',
            $isCashAwaitingVerification => 'Cash payment selected. Please pay at front desk and wait for staff confirmation.',
            $booking->status === 'confirmed' && $booking->payment_status !== 'paid' => 'Complete payment to finalize this reservation.',
            $booking->status === 'confirmed' && $booking->payment_status === 'paid' => 'You are all set. Bring your booking reference at check-in.',
            $booking->status === 'completed' => 'Stay completed. You can download your receipt anytime.',
            default => 'Review your reservation details below.',
        };
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="ta-eyebrow mb-1">Booking Details</p>
            <h1 class="mb-1">Reservation #{{ $booking->id }}</h1>
            <p class="text-secondary mb-0">Track your reservation status and next steps.</p>
        </div>
        <a href="{{ route('bookings.my') }}" class="btn btn-ta-outline">Back to my bookings</a>
    </div>

    <section class="booking-flow mb-4">
        <div class="booking-flow-grid">
            <article class="booking-flow-step {{ $isRequested ? 'is-complete' : '' }}">
                <p class="label mb-0">Step 1</p>
                <p class="status mb-0">Request submitted</p>
            </article>
            <article class="booking-flow-step {{ $isCancelled ? 'is-cancelled' : ($isConfirmed ? 'is-complete' : 'is-current') }}">
                <p class="label mb-0">Step 2</p>
                <p class="status mb-0">{{ $isCancelled ? 'Cancelled' : ($isConfirmed ? 'Confirmed' : 'Awaiting staff review') }}</p>
            </article>
            <article class="booking-flow-step {{ $isCancelled ? 'is-cancelled' : ($isPaid ? 'is-complete' : 'is-current') }}">
                <p class="label mb-0">Step 3</p>
                <p class="status mb-0">{{ $isCancelled ? 'Payment closed' : ($isPaid ? 'Payment received' : ($isCashAwaitingVerification ? 'Cash verification' : ($isOnlineAwaitingVerification ? 'Online verification' : 'Pending payment'))) }}</p>
            </article>
            <article class="booking-flow-step {{ $isCancelled ? 'is-cancelled' : ($isCompleted ? 'is-complete' : 'is-current') }}">
                <p class="label mb-0">Step 4</p>
                <p class="status mb-0">{{ $isCancelled ? 'Booking closed' : ($isCompleted ? 'Stay completed' : 'Upcoming stay') }}</p>
            </article>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="soft-card p-4 p-lg-5">
                <h2 class="h5 mb-3">Stay Information</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Room</small>
                        <strong>{{ $booking->room->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Room type</small>
                        <strong>{{ $booking->room->type ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Room view</small>
                        <strong>{{ $booking->room->view_type ?? 'Not specified' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Check-in</small>
                        <strong>{{ $booking->check_in->format('M d, Y') }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Check-out</small>
                        <strong>{{ $booking->check_out->format('M d, Y') }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Actual arrival</small>
                        <strong>{{ optional($booking->actual_check_in_at)->format('M d, Y h:i A') ?? 'Waiting for staff log' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Actual departure</small>
                        <strong>{{ optional($booking->actual_check_out_at)->format('M d, Y h:i A') ?? 'Not checked out yet' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Guests</small>
                        <strong>{{ $booking->guests }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Nights</small>
                        <strong>{{ $billedUnits }}</strong>
                    </div>
                    @if(!empty($reservationMeta['adults']))
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Adults</small>
                            <strong>{{ $reservationMeta['adults'] }}</strong>
                        </div>
                    @endif
                    @if(isset($reservationMeta['kids']))
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Kids</small>
                            <strong>{{ $reservationMeta['kids'] }}</strong>
                        </div>
                    @endif
                    @if(!empty($reservationMeta['payment_preference']))
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Payment preference</small>
                            <strong>{{ \App\Models\Payment::methodLabel((string) $reservationMeta['payment_preference']) }}</strong>
                        </div>
                    @endif
                    @if(!empty($reservationMeta['discount_type']) && $reservationMeta['discount_type'] !== 'none')
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Discount</small>
                            <strong>{{ strtoupper((string) $reservationMeta['discount_type']) }} (20%)</strong>
                        </div>
                    @endif
                    @if(!empty($reservationMeta['discount_id']))
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Discount ID</small>
                            <strong>{{ $reservationMeta['discount_id'] }}</strong>
                        </div>
                    @endif
                    @if($discountProofUrl !== '')
                        <div class="col-12">
                            <small class="text-secondary d-block">Discount ID photo</small>
                            <a href="{{ $discountProofUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-ta-outline">View uploaded ID photo</a>
                        </div>
                    @endif
                </div>

                @if(!empty($reservationMeta))
                    <hr>
                    <h3 class="h6 mb-3">Guest Contact Details</h3>
                    <div class="row g-3">
                        @if(!empty($reservationMeta['first_name']) || !empty($reservationMeta['last_name']))
                            <div class="col-md-6">
                                <small class="text-secondary d-block">Guest name</small>
                                <strong>{{ trim(($reservationMeta['first_name'] ?? '').' '.($reservationMeta['last_name'] ?? '')) }}</strong>
                            </div>
                        @endif
                        @if(!empty($reservationMeta['contact_email']))
                            <div class="col-md-6">
                                <small class="text-secondary d-block">E-mail</small>
                                <strong>{{ $reservationMeta['contact_email'] }}</strong>
                            </div>
                        @endif
                        @if(!empty($reservationMeta['contact_phone']))
                            <div class="col-md-6">
                                <small class="text-secondary d-block">Phone</small>
                                <strong>{{ $reservationMeta['contact_phone'] }}</strong>
                            </div>
                        @endif
                        @php
                            $guestAddress = collect([
                                $reservationMeta['street_address'] ?? null,
                                $reservationMeta['street_address_line_2'] ?? null,
                                $reservationMeta['guest_city'] ?? null,
                                $reservationMeta['state_province'] ?? null,
                                $reservationMeta['postal_code'] ?? null,
                            ])->filter()->implode(', ');
                        @endphp
                        @if($guestAddress !== '')
                            <div class="col-md-6">
                                <small class="text-secondary d-block">Address</small>
                                <strong>{{ $guestAddress }}</strong>
                            </div>
                        @endif
                    </div>
                @endif

                @if($booking->notes)
                    <hr>
                    <small class="text-secondary d-block">Special request</small>
                    <p class="mb-0">{{ $booking->notes }}</p>
                @endif

                @if($canRequestReschedule || $hasPendingRescheduleRequest)
                    <hr>
                    <h3 class="h6 mb-3">Request Schedule Change</h3>

                    @if($hasPendingRescheduleRequest)
                        <div class="alert alert-info small">
                            Pending staff review:
                            <strong>{{ $booking->requested_check_in?->format('M d, Y') }}</strong>
                            to
                            <strong>{{ $booking->requested_check_out?->format('M d, Y') }}</strong>.
                            @if($booking->reschedule_requested_at)
                                Submitted {{ $booking->reschedule_requested_at->format('M d, Y h:i A') }}.
                            @endif
                        </div>
                        @if(filled($booking->reschedule_request_notes))
                            <p class="small text-secondary mb-3">Request note: <strong>{{ $booking->reschedule_request_notes }}</strong></p>
                        @endif
                    @endif

                    @if($canRequestReschedule)
                        <form method="POST" action="{{ route('bookings.request-reschedule', $booking) }}" class="row g-3">
                            @csrf
                            @method('PATCH')
                            <div class="col-md-6">
                                <label class="form-label">Requested check-in</label>
                                <input
                                    type="date"
                                    name="requested_check_in"
                                    class="form-control @error('requested_check_in') is-invalid @enderror"
                                    min="{{ now()->toDateString() }}"
                                    value="{{ old('requested_check_in', optional($booking->requested_check_in)->toDateString()) }}"
                                    required
                                >
                                @error('requested_check_in')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Requested check-out</label>
                                <input
                                    type="date"
                                    name="requested_check_out"
                                    class="form-control @error('requested_check_out') is-invalid @enderror"
                                    min="{{ now()->addDay()->toDateString() }}"
                                    value="{{ old('requested_check_out', optional($booking->requested_check_out)->toDateString()) }}"
                                    required
                                >
                                @error('requested_check_out')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reason for change</label>
                                <textarea
                                    name="reschedule_request_notes"
                                    class="form-control @error('reschedule_request_notes') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Explain why you need to move the booking dates."
                                >{{ old('reschedule_request_notes', $booking->reschedule_request_notes) }}</textarea>
                                @error('reschedule_request_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-ta">Send reschedule request</button>
                            </div>
                        </form>
                        <p class="small text-secondary mt-2 mb-0">This is available only for confirmed unpaid bookings before check-in.</p>
                    @else
                        <p class="small text-secondary mb-0">Schedule change requests are available only for confirmed unpaid bookings before check-in.</p>
                    @endif
                @endif

                @if($canRequestRoomTransfer || $hasPendingRoomTransferRequest)
                    <hr>
                    <h3 class="h6 mb-3">Request Room Transfer</h3>

                    @if($hasPendingRoomTransferRequest)
                        <div class="alert alert-info small">
                            Your room transfer request is pending staff review.
                            @if($booking->room_transfer_requested_at)
                                Submitted {{ $booking->room_transfer_requested_at->format('M d, Y h:i A') }}.
                            @endif
                        </div>
                        <p class="small text-secondary mb-3">
                            Submitted reason:
                            <strong>{{ $booking->room_transfer_request_reason }}</strong>
                        </p>
                    @endif

                    @if($canRequestRoomTransfer)
                        <form method="POST" action="{{ route('bookings.request-room-transfer', $booking) }}" class="row g-3">
                            @csrf
                            @method('PATCH')
                            <div class="col-12">
                                <label class="form-label">Reason for room transfer</label>
                                <textarea
                                    name="room_transfer_request_reason"
                                    class="form-control @error('room_transfer_request_reason') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Tell us why you need to move to a different room."
                                    required
                                >{{ old('room_transfer_request_reason', $booking->room_transfer_request_reason) }}</textarea>
                                @error('room_transfer_request_reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-ta-outline">Send room transfer request</button>
                            </div>
                        </form>
                        <p class="small text-secondary mt-2 mb-0">Staff will review room availability and your request reason before approving the transfer.</p>
                    @else
                        <p class="small text-secondary mb-0">Room transfer requests are available only for active bookings before final check-out.</p>
                    @endif
                @endif
            </section>
        </div>

        <div class="col-lg-4">
            <section class="soft-card p-4">
                <h2 class="h5 mb-3">Payment & Status</h2>
                <p class="mb-1"><small class="text-secondary">Booking status</small><br><strong>{{ ucfirst($booking->status) }}</strong></p>
                <p class="mb-1"><small class="text-secondary">Payment status</small><br><strong>{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</strong></p>
                @if($isOnlineAwaitingVerification)
                    <p class="small text-secondary mb-1">Method selected: {{ \App\Models\Payment::methodLabel((string) ($booking->payment?->method ?? 'online')) }} (awaiting staff verification)</p>
                @endif
                @if($isCashAwaitingVerification)
                    <p class="small text-secondary mb-1">Method selected: Cash (waiting for staff confirmation)</p>
                @endif
                @if(filled($booking->payment?->customer_reference))
                    <p class="small text-secondary mb-1">Submitted Ref No: <strong>{{ $booking->payment->customer_reference }}</strong></p>
                @endif
                @if($paymentProofUrl !== '')
                    <p class="small text-secondary mb-1">Submitted Proof: <a href="{{ $paymentProofUrl }}" target="_blank" rel="noopener">View uploaded screenshot</a></p>
                @endif
                <p class="mb-3"><small class="text-secondary">Total amount</small><br><strong>&#8369;{{ number_format($booking->total_price, 2) }}</strong></p>

                <div class="booking-next-card mb-3">
                    <small class="text-secondary d-block mb-1">Next action</small>
                    <strong class="small d-block">{{ $nextAction }}</strong>
                </div>

                <p class="small text-secondary mb-3">
                    Actual arrival and departure times are logged by staff during check-in and check-out.
                </p>

                @if($booking->payment?->transaction_reference)
                    <p class="small text-secondary mb-3">Transaction Reference: <strong>{{ $booking->payment->transaction_reference }}</strong></p>
                @endif

                @if($booking->status === 'pending')
                    <div class="alert alert-warning py-2 small">
                        Awaiting staff confirmation. Payment will be enabled once approved.
                    </div>
                @endif
                @if($isCashAwaitingVerification)
                    <div class="alert alert-info py-2 small">
                        Cash payment is not marked as paid automatically. Staff will update payment status after receiving your cash.
                    </div>
                @endif
                @if($isOnlineAwaitingVerification)
                    <div class="alert alert-info py-2 small">
                        Your online payment is waiting for staff verification. We will confirm once the transfer is validated.
                    </div>
                @endif

                <div class="d-grid gap-2">
                    @if(!$isCashAwaitingVerification && !$isOnlineAwaitingVerification && $booking->payment_status !== 'paid' && $booking->status === 'confirmed')
                        <a href="{{ route('payments.checkout', $booking) }}" class="btn btn-ta">Complete payment</a>
                    @endif

                    @if($booking->payment_status === 'paid')
                        <a href="{{ route('bookings.receipt', $booking) }}" class="btn btn-ta-outline">Download receipt (PDF)</a>
                    @endif

                    @if($booking->canBeCancelled())
                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Cancel this booking?')">Cancel booking</button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

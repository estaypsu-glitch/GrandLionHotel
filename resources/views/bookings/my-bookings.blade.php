@extends('layouts.app')

@section('title', 'My Bookings')

@push('head')
    <style>
        .my-bookings-stat {
            border-radius: 14px;
            border: 1px solid #d7deec;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .my-bookings-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .my-bookings-stat .value {
            font-size: clamp(1.3rem, 0.9vw + 0.9rem, 1.7rem);
            line-height: 1;
            font-weight: 800;
            margin: 0;
            color: #b89254;
        }
    </style>
@endpush

@section('content')
    @php
        $statusClass = static function (string $status): string {
            return match ($status) {
                'confirmed' => 'text-bg-success',
                'cancelled' => 'text-bg-danger',
                'completed' => 'text-bg-primary',
                default => 'text-bg-secondary',
            };
        };

        $paymentClass = static function (string $status): string {
            return match ($status) {
                'paid' => 'text-bg-success',
                'pending_verification' => 'text-bg-info',
                'refund_pending' => 'text-bg-info',
                default => 'text-bg-warning',
            };
        };
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="my-bookings-stat">
                <p class="label">Upcoming</p>
                <p class="value">{{ $stats['upcoming'] }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="my-bookings-stat">
                <p class="label">Completed</p>
                <p class="value">{{ $stats['completed'] }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="my-bookings-stat">
                <p class="label">Cancelled</p>
                <p class="value">{{ $stats['cancelled'] }}</p>
            </div>
        </div>
    </div>

    <div class="soft-card p-2 p-lg-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Room</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $isCashAwaitingVerification = $booking->status === 'confirmed'
                                && $booking->payment_status !== 'paid'
                                && strtolower((string) ($booking->payment?->method ?? '')) === 'cash';
                            $isOnlineAwaitingVerification = $booking->status === 'confirmed'
                                && $booking->payment_status === 'pending_verification'
                                && \App\Models\Payment::isOnlineMethod((string) ($booking->payment?->method ?? ''));
                            $hasPendingRescheduleRequest = $booking->hasPendingRescheduleRequest();
                        @endphp
                        <tr>
                            <td>#{{ $booking->id }}</td>
                            <td>{{ $booking->room->name ?? '-' }}</td>
                            <td>
                                <div>{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</div>
                                @if($hasPendingRescheduleRequest)
                                    <small class="text-info">Reschedule requested: {{ $booking->requested_check_in?->format('M d, Y') }} - {{ $booking->requested_check_out?->format('M d, Y') }}</small>
                                @endif
                            </td>
                            <td><span class="badge {{ $statusClass($booking->status) }}">{{ ucfirst($booking->status) }}</span></td>
                            <td>
                                <span class="badge {{ $paymentClass($booking->payment_status) }}">{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</span>
                                @if($isOnlineAwaitingVerification)
                                    <div><small class="text-secondary">Proof submitted, waiting for staff verification</small></div>
                                @endif
                                @if($isCashAwaitingVerification)
                                    <div><small class="text-secondary">Waiting for staff cash confirmation</small></div>
                                @endif
                            </td>
                            <td>&#8369;{{ number_format($booking->total_price, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-ta-outline">Details</a>
                                @if($isOnlineAwaitingVerification)
                                    <span class="small text-secondary d-inline-block ms-2">Under verification</span>
                                @elseif($isCashAwaitingVerification)
                                    <span class="small text-secondary d-inline-block ms-2">Cash selected</span>
                                @elseif($booking->payment_status !== 'paid' && $booking->status === 'confirmed')
                                    <a href="{{ route('payments.checkout', $booking) }}" class="btn btn-sm btn-ta">Pay now</a>
                                @elseif($booking->status === 'pending')
                                    <span class="small text-secondary d-inline-block ms-2">Awaiting staff confirmation</span>
                                @elseif($booking->payment_status === 'paid')
                                    <a href="{{ route('bookings.receipt', $booking) }}" class="btn btn-sm btn-ta">Receipt</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No bookings found yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $bookings->links() }}
    </div>
@endsection

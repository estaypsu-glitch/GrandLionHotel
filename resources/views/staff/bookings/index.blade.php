@extends('layouts.staff')

@section('title', 'Bookings')

@push('head')
    <style>
        .ops-booking-shell,
        .ops-booking-table-shell {
            border-radius: 14px;
            border: 1px solid #d9e1ef;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .ops-queue-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .ops-queue-tab {
            border-radius: 999px;
            border: 1px solid #d5deee;
            background: #f8fbff;
            color: #334155;
            font-size: 0.76rem;
            font-weight: 700;
            text-decoration: none;
            padding: 0.32rem 0.66rem;
            display: inline-flex;
            align-items: center;
            gap: 0.38rem;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }
        .ops-queue-tab .count {
            border-radius: 999px;
            min-width: 1.45rem;
            height: 1.35rem;
            padding: 0 0.36rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.68rem;
            font-weight: 800;
            background: #e2e8f0;
            color: #1e293b;
        }
        .ops-queue-tab.active {
            border-color: #1d4ed8;
            background: #1d4ed8;
            color: #fff;
        }
        .ops-queue-tab.active .count {
            background: rgba(255, 255, 255, 0.24);
            color: #fff;
        }
        .ops-queue-tab:hover {
            border-color: #a8c0ea;
        }
        .ops-filter-row {
            margin-top: 0.85rem;
        }
        .ops-filter-chip-wrap {
            margin-top: 0.9rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .ops-filter-chip {
            border-radius: 999px;
            border: 1px solid #d7deec;
            background: #f8fbff;
            color: #334155;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.22rem 0.58rem;
        }
        .ops-filter-chip strong {
            color: #1f2937;
            font-weight: 800;
        }
        .ops-booking-table {
            margin-bottom: 0;
        }
        .ops-booking-table thead th {
            font-size: 0.7rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #5a6a80;
            white-space: nowrap;
            border-top: 0;
            padding-top: 0.78rem;
            padding-bottom: 0.62rem;
        }
        .ops-booking-table td {
            vertical-align: top;
            border-color: #edf2f7;
            font-size: 0.9rem;
        }
        .ops-booking-table tbody tr:hover {
            background: #f8fbff;
        }
        .ops-priority {
            border-radius: 999px;
            font-size: 0.66rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 800;
            padding: 0.22rem 0.54rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .ops-priority.high {
            background: rgba(220, 38, 38, 0.12);
            color: #991b1b;
            border-color: rgba(220, 38, 38, 0.25);
        }
        .ops-priority.medium {
            background: rgba(217, 119, 6, 0.12);
            color: #92400e;
            border-color: rgba(217, 119, 6, 0.25);
        }
        .ops-priority.low {
            background: rgba(37, 99, 235, 0.12);
            color: #1e40af;
            border-color: rgba(37, 99, 235, 0.25);
        }
        .ops-guest-name {
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.2rem;
        }
        .ops-guest-meta {
            color: #64748b;
            font-size: 0.8rem;
        }
        .ops-status-stack {
            display: grid;
            gap: 0.38rem;
            min-width: 150px;
        }
        .ops-action-stack {
            display: inline-flex;
            flex-wrap: nowrap;
            justify-content: flex-end;
            gap: 0.35rem;
            min-width: 320px;
        }
        .ops-action-stack form {
            margin: 0;
        }
        .ops-action-stack .btn {
            min-height: 34px;
            min-width: 88px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            padding: 0.38rem 0.68rem;
        }
        .ops-action-stack .btn-staff {
            box-shadow: 0 5px 10px rgba(var(--theme-primary-rgb), 0.18);
        }
        .ops-action-stack .btn-staff:hover {
            box-shadow: 0 8px 14px rgba(var(--theme-secondary-rgb), 0.2);
        }
        .ops-empty {
            text-align: center;
            padding: 2.4rem 1rem;
            color: #64748b;
            font-weight: 600;
        }
        .ops-actions-col {
            min-width: 340px;
        }
        .ops-summary-card {
            border-radius: 14px;
            border: 1px solid #d7deec;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .ops-summary-card .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .ops-summary-card .value {
            font-size: clamp(1.3rem, 0.9vw + 0.9rem, 1.7rem);
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .ops-page-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 0.85rem;
        }
    </style>
@endpush

@section('content')
    @php
        $activeQueue = (string) ($queue ?? request('queue'));
        $queueLinks = [
            '' => 'All bookings',
            'pending' => 'Pending approval',
            'arrivals_today' => "Today's arrivals",
            'departures_today' => "Today's departures",
            'in_house' => 'In-house guests',
        ];

        $statusBadgeClass = static function (string $status): string {
            return match ($status) {
                'confirmed' => 'text-bg-success',
                'cancelled' => 'text-bg-danger',
                'completed' => 'text-bg-primary',
                default => 'text-bg-secondary',
            };
        };

        $paymentBadgeClass = static function (string $status): string {
            return match ($status) {
                'paid' => 'text-bg-success',
                'pending_verification' => 'text-bg-info',
                'refund_pending' => 'text-bg-info',
                default => 'text-bg-warning',
            };
        };

        $activeFilters = [];
        if (filled(request('q'))) {
            $activeFilters[] = ['label' => 'Search', 'value' => request('q')];
        }
        if (filled(request('status'))) {
            $activeFilters[] = ['label' => 'Status', 'value' => ucfirst((string) request('status'))];
        }
        if (filled(request('payment_status'))) {
            $activeFilters[] = ['label' => 'Payment', 'value' => ucfirst(str_replace('_', ' ', (string) request('payment_status')))];
        }
        if ($activeQueue !== '') {
            $activeFilters[] = ['label' => 'Queue', 'value' => $queueLinks[$activeQueue] ?? ucfirst(str_replace('_', ' ', $activeQueue))];
        }
    @endphp

    <section class="mb-4">
        <div class="ops-page-head">
            <div>
                <h1 class="h4 mb-1">Bookings Board</h1>
            </div>
            <a href="{{ route('staff.bookings.create') }}" class="btn btn-staff">
                <i class="bi bi-plus-circle"></i>
                <span>New Walk-in</span>
            </a>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="ops-summary-card">
                <p class="label">Pending Approvals</p>
                <p class="value text-danger">{{ $queueMeta['pending_approvals'] ?? 0 }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="ops-summary-card">
                <p class="label">Today's Arrivals</p>
                <p class="value text-primary">{{ $queueMeta['arrivals_today'] ?? 0 }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="ops-summary-card">
                <p class="label">Today's Departures</p>
                <p class="value text-info">{{ $queueMeta['departures_today'] ?? 0 }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="ops-summary-card">
                <p class="label">Unpaid / For Verification</p>
                <p class="value text-warning">{{ $queueMeta['unpaid_confirmed'] ?? 0 }}</p>
            </div>
        </div>
    </section>

    <section class="ops-booking-shell p-3 p-lg-4 mb-4">
        <div class="ops-queue-tabs">
            @foreach($queueLinks as $queueValue => $queueLabel)
                <a
                    href="{{ route('staff.bookings.index', array_merge(request()->except(['page', 'queue']), $queueValue === '' ? [] : ['queue' => $queueValue])) }}"
                    class="ops-queue-tab {{ $activeQueue === $queueValue ? 'active' : '' }}"
                >
                    <span>{{ $queueLabel }}</span>
                    <span class="count">{{ $queueCounts[$queueValue] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('staff.bookings.index') }}" class="ops-filter-row">
            <input type="hidden" name="queue" value="{{ $activeQueue }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Search guest or room</label>
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Guest name, booking ID, room type...">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label fw-semibold">Booking status</label>
                    <select class="form-select" name="status">
                        <option value="">All</option>
                        @foreach(['pending', 'confirmed', 'cancelled', 'completed'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label fw-semibold">Payment status</label>
                    <select class="form-select" name="payment_status">
                        <option value="">All</option>
                        @foreach(['unpaid', 'pending_verification', 'paid', 'refund_pending'] as $paymentStatus)
                            <option value="{{ $paymentStatus }}" @selected(request('payment_status') === $paymentStatus)>{{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-staff w-100">Apply</button>
                    <a href="{{ route('staff.bookings.index', $activeQueue === '' ? [] : ['queue' => $activeQueue]) }}" class="btn btn-staff-outline">Reset</a>
                </div>
            </div>
        </form>

        @if(!empty($activeFilters))
            <div class="ops-filter-chip-wrap">
                @foreach($activeFilters as $filter)
                    <span class="ops-filter-chip">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong></span>
                @endforeach
            </div>
        @endif
    </section>

    <section class="ops-booking-table-shell p-2 p-lg-3">
        <div class="table-responsive">
            <table class="table ops-booking-table align-middle">
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Guest / Stay</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end ops-actions-col staff-action-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $isArrivalToday = $booking->check_in->isToday() && is_null($booking->actual_check_in_at);
                            $isDepartureToday = $booking->check_out->isToday() && !is_null($booking->actual_check_in_at) && is_null($booking->actual_check_out_at);
                            $hasPendingRescheduleRequest = $booking->hasPendingRescheduleRequest();

                            if ($booking->status === 'pending') {
                                $priorityTone = 'high';
                                $priorityText = 'Approve';
                            } elseif ($hasPendingRescheduleRequest) {
                                $priorityTone = 'high';
                                $priorityText = 'Reschedule';
                            } elseif ($booking->status === 'confirmed' && $booking->payment_status === 'pending_verification') {
                                $priorityTone = 'high';
                                $priorityText = 'Verify payment';
                            } elseif ($booking->canBeCheckedInByStaff() && $isArrivalToday) {
                                $priorityTone = 'high';
                                $priorityText = 'Check-in due';
                            } elseif ($booking->status === 'confirmed' && $booking->payment_status === 'unpaid') {
                                $priorityTone = 'medium';
                                $priorityText = 'Collect payment';
                            } elseif ($booking->canBeCheckedOutByStaff() && $isDepartureToday) {
                                $priorityTone = 'medium';
                                $priorityText = 'Check-out due';
                            } else {
                                $priorityTone = 'low';
                                $priorityText = 'Monitor';
                            }
                        @endphp

                        <tr>
                            <td>
                                <span class="ops-priority {{ $priorityTone }}">{{ $priorityText }}</span>
                            </td>
                            <td>
                                <div class="ops-guest-name">{{ $booking->guestName() }}</div>
                                <div class="ops-guest-meta">#{{ $booking->id }} &middot; {{ $booking->room->name ?? '-' }}</div>
                                <div class="ops-guest-meta">{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</div>
                                @if($hasPendingRescheduleRequest)
                                    <div class="ops-guest-meta text-info">
                                        Requested: {{ $booking->requested_check_in?->format('M d, Y') }} - {{ $booking->requested_check_out?->format('M d, Y') }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="ops-status-stack">
                                    <span class="badge {{ $statusBadgeClass($booking->status) }}">{{ ucfirst($booking->status) }}</span>
                                    @if($booking->actual_check_in_at && !$booking->actual_check_out_at)
                                        <span class="badge text-bg-primary">Checked in</span>
                                    @elseif($booking->actual_check_out_at)
                                        <span class="badge text-bg-dark">Checked out</span>
                                    @else
                                        <span class="badge text-bg-secondary">Not arrived</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="ops-status-stack">
                                    <span class="badge {{ $paymentBadgeClass($booking->payment_status) }}">{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</span>
                                    @if($booking->status === 'confirmed' && $booking->payment_status === 'pending_verification')
                                        <span class="ops-guest-meta">Customer proof submitted</span>
                                    @endif
                                    @if($booking->status === 'confirmed' && $booking->payment_status === 'unpaid')
                                        <span class="ops-guest-meta">Collect before checkout</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end ops-actions-col staff-action-col">
                                <div class="ops-action-stack staff-action-group">
                                    @if($booking->canBeConfirmedByStaff())
                                        <form method="POST" action="{{ route('staff.bookings.confirm', $booking) }}" data-confirm="Confirm this booking?">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-staff" type="submit">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>Confirm</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($booking->canBeCheckedInByStaff())
                                        <form method="POST" action="{{ route('staff.bookings.check-in', $booking) }}" data-confirm="Check in this guest now?">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="actual_check_in_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                                            <button class="btn btn-sm btn-staff" type="submit">
                                                <i class="bi bi-door-open"></i>
                                                <span>Check-in</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($booking->canBeCheckedOutByStaff() && $booking->payment_status === 'paid')
                                        <form method="POST" action="{{ route('staff.bookings.check-out', $booking) }}" data-confirm="Check out this guest and complete booking?">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="actual_check_out_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                                            <button class="btn btn-sm btn-staff" type="submit">
                                                <i class="bi bi-door-closed"></i>
                                                <span>Check-out</span>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('staff.bookings.show', ['booking' => $booking, 'return_to' => request()->getRequestUri()]) }}" class="btn btn-sm btn-staff-outline">
                                        <i class="bi bi-eye"></i>
                                        <span>Details</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ops-empty">No bookings found for the selected queue and filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-3">
        {{ $bookings->links() }}
    </div>
@endsection

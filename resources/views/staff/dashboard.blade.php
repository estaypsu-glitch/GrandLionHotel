@extends('layouts.staff')

@section('title', 'Dashboard')

@push('head')
    <style>
        .ops-focus-card {
            border: 1px solid #d7deec;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            text-decoration: none;
            display: block;
            padding: 0.86rem 0.92rem;
            height: 100%;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .ops-focus-card:hover {
            border-color: #c3d2e3;
            box-shadow: 0 14px 26px rgba(15, 23, 42, 0.12);
        }
        .ops-focus-eyebrow {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 700;
            color: #617084;
        }
        .ops-focus-metric {
            font-size: clamp(1.45rem, 1vw + 1rem, 1.95rem);
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .ops-health-shell,
        .ops-queue-card {
            border-radius: 14px;
            border: 1px solid #d9e1ef;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .ops-health-item {
            border: 1px solid #e3e8f2;
            border-radius: 12px;
            background: #f9fbff;
            padding: 0.72rem 0.8rem;
            height: 100%;
        }
        .ops-health-item .label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #66758d;
            font-weight: 700;
            margin-bottom: 0.32rem;
        }
        .ops-health-item .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
        }
        .ops-queue-title {
            font-size: 1rem;
            margin-bottom: 0;
        }
        .ops-queue-count {
            border-radius: 999px;
            border: 1px solid #d6e2ff;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 0.22rem 0.62rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
        }
        .ops-table {
            margin-bottom: 0;
        }
        .ops-table thead th {
            font-size: 0.7rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #5a6a80;
            white-space: nowrap;
            border-top: 0;
            padding-top: 0.78rem;
            padding-bottom: 0.62rem;
        }
        .ops-table tbody td {
            vertical-align: middle;
            border-color: #edf2f7;
            font-size: 0.9rem;
        }
        .ops-table tbody tr:hover {
            background: #f8fbff;
        }
        .ops-subtext {
            color: #6b7280;
            font-size: 0.8rem;
        }
        .ops-date {
            font-weight: 700;
            color: #1f2937;
        }
        .ops-section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.95rem;
        }
    </style>
@endpush

@section('content')
    @php
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
    @endphp

    <section class="mb-4">
        <div class="ops-section-head">
            <div>
                <h1 class="h4 mb-1">Shift Priorities</h1>
            </div>
            <a href="{{ route('staff.arrivals') }}" class="btn btn-sm btn-staff-outline">Open Arrivals Board</a>
        </div>

        <div class="row g-3">
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('staff.arrivals') }}" class="ops-focus-card">
                    <p class="ops-focus-eyebrow mb-0">Today's Arrivals</p>
                    <p class="ops-focus-metric text-primary">{{ $stats['arrivals_today'] }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('staff.bookings.index', ['queue' => 'departures_today']) }}" class="ops-focus-card">
                    <p class="ops-focus-eyebrow mb-0">Today's Departures</p>
                    <p class="ops-focus-metric text-warning">{{ $stats['departures_today'] }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('staff.bookings.index', ['queue' => 'in_house']) }}" class="ops-focus-card">
                    <p class="ops-focus-eyebrow mb-0">In-House Guests</p>
                    <p class="ops-focus-metric text-success">{{ $stats['in_house'] }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('staff.bookings.index', ['status' => 'pending']) }}" class="ops-focus-card">
                    <p class="ops-focus-eyebrow mb-0">Pending Approval</p>
                    <p class="ops-focus-metric text-info">{{ $stats['pending_bookings'] }}</p>
                </a>
            </div>
        </div>
    </section>

    <section class="ops-health-shell p-3 p-lg-4 mb-4">
        <div class="ops-section-head mb-3">
            <div>
                <h2 class="h5 mb-1">Booking Health</h2>
            </div>
            <a href="{{ route('staff.bookings.index') }}" class="btn btn-sm btn-staff">Manage All Bookings</a>
        </div>

        <div class="row g-3">
            <div class="col-sm-6 col-xl-3">
                <div class="ops-health-item">
                    <p class="label">Unpaid / For Verification</p>
                    <p class="value text-danger mb-0">{{ $stats['unpaid_confirmed'] }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="ops-health-item">
                    <p class="label">Confirmed</p>
                    <p class="value text-success mb-0">{{ $stats['confirmed_bookings'] }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="ops-health-item">
                    <p class="label">Completed</p>
                    <p class="value text-primary mb-0">{{ $stats['completed_bookings'] }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="ops-health-item">
                    <p class="label">Cancelled</p>
                    <p class="value text-muted mb-0">{{ $stats['cancelled_bookings'] }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-xxl-6">
            <section class="ops-queue-card p-3 p-lg-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="ops-queue-title">Today's Arrivals</h3>
                    <span class="ops-queue-count">{{ $arrivalsToday->count() }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table ops-table align-middle">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th class="text-end staff-action-col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($arrivalsToday as $booking)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $booking->guestName() }}</div>
                                        <div class="ops-subtext">#{{ $booking->id }} &middot; <span class="ops-date">{{ $booking->check_in->format('M d') }}</span></div>
                                    </td>
                                    <td>{{ $booking->room->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadgeClass($booking->status) }}">{{ ucfirst($booking->status) }}</span>
                                    </td>
                                    <td class="text-end staff-action-col">
                                        <div class="staff-action-group">
                                            <a href="{{ route('staff.bookings.show', ['booking' => $booking, 'return_to' => request()->getRequestUri()]) }}" class="btn btn-sm btn-staff-outline">
                                                <i class="bi bi-eye"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-secondary">No arrivals queued today.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-12 col-xxl-6">
            <section class="ops-queue-card p-3 p-lg-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="ops-queue-title">In-House Guests</h3>
                    <span class="ops-queue-count">{{ $inHouseGuests->count() }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table ops-table align-middle">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Departure</th>
                                <th>Payment</th>
                                <th class="text-end staff-action-col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inHouseGuests as $booking)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $booking->guestName() }}</div>
                                        <div class="ops-subtext">{{ $booking->room->name ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="ops-date">{{ $booking->check_out->format('M d') }}</span>
                                        <div class="ops-subtext">{{ optional($booking->actual_check_in_at)->format('h:i A') ?? 'No check-in time' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $paymentBadgeClass($booking->payment_status) }}">{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</span>
                                    </td>
                                    <td class="text-end staff-action-col">
                                        <div class="staff-action-group">
                                            <a href="{{ route('staff.bookings.show', ['booking' => $booking, 'return_to' => request()->getRequestUri()]) }}" class="btn btn-sm btn-staff-outline">
                                                <i class="bi bi-eye"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-secondary">No in-house guests right now.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
@endsection

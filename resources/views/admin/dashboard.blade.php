@extends('layouts.admin')

@section('title', 'Dashboard')

@push('head')
    <style>
        .admin-focus-card {
            border: 1px solid var(--admin-line);
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            padding: 0.86rem 0.92rem;
            height: 100%;
            text-decoration: none;
            display: block;
            color: inherit;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .admin-focus-card:hover {
            border-color: var(--admin-line-strong);
            box-shadow: 0 14px 26px rgba(15, 23, 42, 0.12);
            color: inherit;
        }
        .admin-focus-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.34rem;
        }
        .admin-focus-value {
            font-size: clamp(1.4rem, 2vw, 2rem);
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .admin-priority-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .admin-priority-list {
            display: grid;
            gap: 0.6rem;
        }
        .admin-priority-item {
            border: 1px solid #dce6f3;
            border-radius: 12px;
            background: #f8fbff;
            padding: 0.64rem 0.74rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem;
        }
        .admin-priority-item .title {
            font-weight: 700;
            font-size: 0.92rem;
            margin: 0;
        }
        .admin-priority-count {
            border-radius: 999px;
            min-width: 1.95rem;
            padding: 0.18rem 0.56rem;
            font-size: 0.74rem;
            font-weight: 800;
            text-align: center;
        }
        .admin-action-grid {
            display: grid;
            gap: 0.6rem;
        }
        .admin-action-grid .btn {
            justify-content: flex-start;
        }
        .admin-recent-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .admin-recent-table thead th {
            font-size: 0.7rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #5a6a80;
            white-space: nowrap;
        }
        .admin-recent-table tbody td {
            vertical-align: middle;
            border-color: #edf2f7;
            font-size: 0.9rem;
        }
        .admin-recent-table tbody tr:hover {
            background: #f8fbff;
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

    <section class="mb-4">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-0">Dashboard</h1>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-ta">
                <i class="bi bi-journal-check me-1"></i>Open Booking Desk
            </a>
        </div>

        <div class="row g-3">
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('admin.bookings.index') }}" class="admin-focus-card">
                    <p class="admin-focus-label">Total Bookings</p>
                    <p class="admin-focus-value">{{ $stats['bookings'] }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="admin-focus-card">
                    <p class="admin-focus-label">Pending Approval</p>
                    <p class="admin-focus-value text-warning">{{ $stats['pending_bookings'] }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('admin.bookings.index', ['status' => 'confirmed', 'payment_status' => 'unpaid']) }}" class="admin-focus-card">
                    <p class="admin-focus-label">Unpaid / For Verification</p>
                    <p class="admin-focus-value text-danger">{{ $stats['unpaid_confirmed'] }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('admin.rooms.index') }}" class="admin-focus-card">
                    <p class="admin-focus-label">Rooms Attention</p>
                    <p class="admin-focus-value text-info">{{ $stats['rooms_needing_attention'] }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('admin.bookings.index', ['payment_status' => 'paid']) }}" class="admin-focus-card">
                    <p class="admin-focus-label">Year to date</p>
                    <p class="admin-focus-value">&#8369;{{ number_format((float) $stats['paid_revenue'], 2) }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('admin.bookings.index', ['payment_status' => 'paid']) }}" class="admin-focus-card">
                    <p class="admin-focus-label">Month to date</p>
                    <p class="admin-focus-value">&#8369;{{ number_format((float) $stats['monthly_paid_revenue'], 2) }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="admin-focus-card">
                    <p class="admin-focus-label">Arrivals Today</p>
                    <p class="admin-focus-value text-primary">{{ $stats['arrivals_today'] }}</p>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('admin.bookings.index', ['status' => 'confirmed']) }}" class="admin-focus-card">
                    <p class="admin-focus-label">Departures Today</p>
                    <p class="admin-focus-value text-success">{{ $stats['departures_today'] }}</p>
                </a>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="admin-priority-shell p-3 p-lg-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Priority Queues</h2>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-ta-outline">Open Bookings</a>
                </div>

                <div class="admin-priority-list">
                    <a class="admin-priority-item text-decoration-none text-reset" href="{{ route('admin.bookings.index', ['status' => 'pending']) }}">
                        <div>
                            <p class="title">Pending booking approvals</p>
                        </div>
                        <span class="admin-priority-count text-bg-warning">{{ $stats['pending_bookings'] }}</span>
                    </a>

                    <a class="admin-priority-item text-decoration-none text-reset" href="{{ route('admin.bookings.index', ['status' => 'confirmed', 'payment_status' => 'unpaid']) }}">
                        <div>
                            <p class="title">Confirmed bookings awaiting payment</p>
                        </div>
                        <span class="admin-priority-count text-bg-danger">{{ $stats['unpaid_confirmed'] }}</span>
                    </a>

                    <a class="admin-priority-item text-decoration-none text-reset" href="{{ route('admin.rooms.index') }}">
                        <div>
                            <p class="title">Rooms needing operational attention</p>
                        </div>
                        <span class="admin-priority-count text-bg-info">{{ $stats['rooms_needing_attention'] }}</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-priority-shell p-3 p-lg-4 h-100">
                <h2 class="h5 mb-3">Quick Actions</h2>
                <div class="admin-action-grid">
                    <a href="{{ route('admin.rooms.create') }}" class="btn btn-ta">
                        <i class="bi bi-plus-circle me-1"></i>Add New Room
                    </a>
                    <a href="{{ route('admin.staff.create') }}" class="btn btn-ta-outline">
                        <i class="bi bi-person-plus me-1"></i>Create Staff Account
                    </a>
                    <a href="{{ route('admin.sales-report') }}" class="btn btn-ta-outline">
                        <i class="bi bi-bar-chart-line me-1"></i>Open Sales Report
                    </a>
                    <a href="{{ route('admin.users.index', ['profile' => 'incomplete']) }}" class="btn btn-ta-outline">
                        <i class="bi bi-person-exclamation me-1"></i>Review Incomplete Profiles
                    </a>
                    <a href="{{ route('admin.bookings.index', ['payment_status' => 'refund_pending']) }}" class="btn btn-ta-outline">
                        <i class="bi bi-receipt-cutoff me-1"></i>Check Refund Queue
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-recent-shell p-2 p-lg-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-2 px-lg-1 pt-1 mb-2">
            <div>
                <h2 class="h5 mb-0">Latest Booking Activity</h2>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-ta-outline">View All</a>
        </div>

        <div class="table-responsive">
            <table class="table admin-recent-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Guest</th>
                        <th>Room Type</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Assigned Staff</th>
                        <th class="text-end admin-action-col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBookings as $booking)
                        <tr>
                            <td>#{{ $booking->id }}</td>
                            <td>{{ $booking->guestName() }}</td>
                            <td>{{ $booking->room->name ?? '-' }}</td>
                            <td>{{ $booking->check_in->format('M d') }} - {{ $booking->check_out->format('M d') }}</td>
                            <td><span class="badge {{ $statusClass($booking->status) }}">{{ ucfirst($booking->status) }}</span></td>
                            <td><span class="badge {{ $paymentClass($booking->payment_status) }}">{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</span></td>
                            <td>{{ $booking->assignedStaff?->name ?? '-' }}</td>
                            <td class="text-end admin-action-col">
                                <div class="admin-action-group">
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-ta-outline">
                                        <i class="bi bi-eye"></i>
                                        <span>View</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">No recent bookings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

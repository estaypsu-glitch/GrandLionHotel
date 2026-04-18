@extends('layouts.admin')

@section('title', 'Bookings')

@push('head')
    <style>
        .admin-booking-stat {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            box-shadow: var(--admin-shadow);
            background: linear-gradient(180deg, var(--admin-surface) 0%, #f8fbff 100%);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .admin-booking-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .admin-booking-stat .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .admin-bookings-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: var(--admin-shadow);
        }
        .admin-filter-chip-wrap {
            margin-top: 0.8rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .admin-filter-chip {
            border-radius: 999px;
            border: 1px solid var(--admin-line);
            background: #f8fbff;
            color: #4f6074;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.22rem 0.58rem;
        }
        .admin-filter-chip strong {
            color: #1a2738;
            font-weight: 800;
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
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0">Booking Management</h1>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-ta-outline">Back to dashboard</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="admin-booking-stat">
                <p class="label">Total</p>
                <p class="value">{{ $summary['total'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="admin-booking-stat">
                <p class="label">Pending</p>
                <p class="value text-warning">{{ $summary['pending'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="admin-booking-stat">
                <p class="label">Confirmed</p>
                <p class="value text-success">{{ $summary['confirmed'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="admin-booking-stat">
                <p class="label">Completed</p>
                <p class="value text-primary">{{ $summary['completed'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="admin-booking-stat">
                <p class="label">Unpaid / For Verification</p>
                <p class="value text-danger">{{ $summary['unpaid_confirmed'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="admin-booking-stat">
                <p class="label">Paid Sales</p>
                <p class="value">&#8369;{{ number_format((float) $summary['paid_revenue'], 2) }}</p>
            </div>
        </div>
    </div>

    <section class="admin-bookings-shell p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('admin.bookings.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">Search user or room</label>
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Booking ID, user name, room name...">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Booking status</label>
                    <select class="form-select" name="status">
                        <option value="">All</option>
                        @foreach(['pending', 'confirmed', 'cancelled', 'completed'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Payment status</label>
                    <select class="form-select" name="payment_status">
                        <option value="">All</option>
                        @foreach(['unpaid', 'pending_verification', 'paid', 'refund_pending'] as $paymentStatus)
                            <option value="{{ $paymentStatus }}" @selected(request('payment_status') === $paymentStatus)>{{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-ta w-100">Apply</button>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-ta-outline">Reset</a>
                </div>
            </div>
        </form>

        @if(!empty($activeFilters))
            <div class="admin-filter-chip-wrap">
                @foreach($activeFilters as $filter)
                    <span class="admin-filter-chip">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong></span>
                @endforeach
            </div>
        @endif
    </section>

    <div class="table-shell p-2 p-lg-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Room Type</th>
                        <th>Check in/out Dates</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Assigned Staff</th>
                        <th class="text-end admin-action-col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>#{{ $booking->id }}</td>
                            <td>{{ $booking->guestName() }}</td>
                            <td>{{ $booking->room->name ?? '-' }}</td>
                            <td>{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</td>
                            <td><span class="badge {{ $statusClass($booking->status) }}">{{ ucfirst($booking->status) }}</span></td>
                            <td><span class="badge {{ $paymentClass($booking->payment_status) }}">{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</span></td>
                            <td>{{ $booking->assignedStaff->name ?? '-' }}</td>
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
                            <td colspan="8" class="text-center py-4">No bookings found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $bookings->links() }}
    </div>
@endsection

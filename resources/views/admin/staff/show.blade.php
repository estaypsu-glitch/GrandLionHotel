@extends('layouts.admin')

@section('title', 'Staff Customer Assignments')

@push('head')
    <style>
        .staff-assignment-stat {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            box-shadow: var(--admin-shadow);
            background: linear-gradient(180deg, var(--admin-surface) 0%, #f8fbff 100%);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .staff-assignment-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .staff-assignment-stat .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .staff-assignment-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: var(--admin-shadow);
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

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $staff->name }} - Customer Assignments</h1>
            <p class="text-secondary mb-0">
                Daily sales ({{ $selectedDateLabel }}):
                <strong>&#8369;{{ number_format((float) $stats['paid_revenue_for_date'], 2) }}</strong>
                from {{ $stats['paid_bookings_for_date'] }} paid booking(s)
            </p>
        </div>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-ta-outline">Back to staff list</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="staff-assignment-stat">
                <p class="label">Assigned Bookings</p>
                <p class="value">{{ $stats['assigned_total'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="staff-assignment-stat">
                <p class="label">Active</p>
                <p class="value text-warning">{{ $stats['pending_or_confirmed'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="staff-assignment-stat">
                <p class="label">Completed</p>
                <p class="value text-success">{{ $stats['completed'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="staff-assignment-stat">
                <p class="label">Cancelled</p>
                <p class="value text-danger">{{ $stats['cancelled'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="staff-assignment-stat">
                <p class="label">Unique Customers</p>
                <p class="value text-info">{{ $stats['customers'] }}</p>
            </div>
        </div>
    </div>

    <section class="staff-assignment-shell p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('admin.staff.show', $staff) }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">Search booking/customer</label>
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Booking ID, guest name, room...">
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
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label">Earnings date</label>
                    <input type="date" class="form-control" name="earnings_date" value="{{ $selectedDateString }}">
                </div>
                <div class="col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-ta w-100">Apply</button>
                    <a href="{{ route('admin.staff.show', $staff) }}" class="btn btn-ta-outline">Reset</a>
                </div>
            </div>
        </form>
    </section>

    <div class="table-shell p-2 p-lg-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Room</th>
                        <th>Stay Dates</th>
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
                            <td>
                                <div>{{ $booking->guestEmail() }}</div>
                                <small class="text-secondary">{{ $booking->guestPhone() }}</small>
                            </td>
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
                            <td colspan="9" class="text-center py-4">No assigned customers/bookings found for this staff.</td>
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

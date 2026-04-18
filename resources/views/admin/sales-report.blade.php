@extends('layouts.admin')

@section('title', 'Sales Report')

@push('head')
    <style>
        .admin-report-stat {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: var(--admin-shadow);
            padding: 0.82rem 0.9rem;
            height: 100%;
        }
        .admin-report-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        .admin-report-stat .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .admin-report-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: var(--admin-shadow);
        }
        .admin-report-table thead th {
            font-size: 0.7rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #5a6a80;
            white-space: nowrap;
        }
        .admin-report-table tbody td {
            vertical-align: middle;
            border-color: #edf2f7;
            font-size: 0.9rem;
        }
    </style>
@endpush

@section('content')
    @php
        $methodLabel = static function (string $value): string {
            return match ($value) {
                'bank_transfer' => 'Bank Transfer',
                'gcash' => 'GCash',
                'paymaya' => 'PayMaya',
                'cash' => 'Cash',
                default => ucfirst(str_replace('_', ' ', $value)),
            };
        };
    @endphp

    <section class="mb-4">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Sales Report</h1>
                <p class="text-secondary mb-0">Paid sales for {{ $selectedRangeLabel }}</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ta-outline">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>

        <section class="admin-report-shell p-3 p-lg-4 mb-4">
            <form method="GET" action="{{ route('admin.sales-report') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from" class="form-control" value="{{ $from }}">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to" class="form-control" value="{{ $to }}">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">Payment Method</label>
                        <select name="method" class="form-select">
                            <option value="all" @selected($method === 'all')>All methods</option>
                            <option value="cash" @selected($method === 'cash')>Cash</option>
                            <option value="bank_transfer" @selected($method === 'bank_transfer')>Bank Transfer</option>
                            <option value="gcash" @selected($method === 'gcash')>GCash</option>
                            <option value="paymaya" @selected($method === 'paymaya')>PayMaya</option>
                        </select>
                    </div>
                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-ta w-100">Apply</button>
                        <a href="{{ route('admin.sales-report') }}" class="btn btn-ta-outline">Reset</a>
                    </div>
                </div>
            </form>
        </section>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-2">
                <div class="admin-report-stat">
                    <p class="label">Total Sales</p>
                    <p class="value">&#8369;{{ number_format((float) $summary['total_revenue'], 2) }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="admin-report-stat">
                    <p class="label">Paid Bookings</p>
                    <p class="value text-primary">{{ $summary['paid_bookings'] }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="admin-report-stat">
                    <p class="label">Average Sale</p>
                    <p class="value text-success">&#8369;{{ number_format((float) $summary['average_sale'], 2) }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="admin-report-stat">
                    <p class="label">Discount Total</p>
                    <p class="value text-warning">&#8369;{{ number_format((float) $summary['total_discount'], 2) }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="admin-report-stat">
                    <p class="label">Online Payments</p>
                    <p class="value text-info">{{ $summary['online_payments'] }}</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="admin-report-stat">
                    <p class="label">Cash Payments</p>
                    <p class="value">{{ $summary['cash_payments'] }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="admin-report-shell p-2 p-lg-3 h-100">
                <div class="d-flex justify-content-between align-items-center px-2 pt-1 mb-2">
                    <h2 class="h5 mb-0">Daily Sales</h2>
                </div>
                <div class="table-responsive">
                    <table class="table admin-report-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Paid Bookings</th>
                                <th>Discount Total</th>
                                <th class="text-end">Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailySales as $day)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                                    <td>{{ $day->paid_bookings }}</td>
                                    <td>&#8369;{{ number_format((float) $day->discount_total, 2) }}</td>
                                    <td class="text-end fw-semibold">&#8369;{{ number_format((float) $day->revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-secondary">No paid sales found for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="admin-report-shell p-2 p-lg-3 h-100">
                <div class="d-flex justify-content-between align-items-center px-2 pt-1 mb-2">
                    <h2 class="h5 mb-0">Payment Methods</h2>
                </div>
                <div class="table-responsive">
                    <table class="table admin-report-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Paid Bookings</th>
                                <th class="text-end">Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($methodBreakdown as $row)
                                <tr>
                                    <td>{{ $methodLabel((string) $row->method) }}</td>
                                    <td>{{ $row->paid_bookings }}</td>
                                    <td class="text-end fw-semibold">&#8369;{{ number_format((float) $row->revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-secondary">No payment method totals yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-xl-5">
            <div class="admin-report-shell p-2 p-lg-3 h-100">
                <div class="d-flex justify-content-between align-items-center px-2 pt-1 mb-2">
                    <h2 class="h5 mb-0">Staff Performance</h2>
                </div>
                <div class="table-responsive">
                    <table class="table admin-report-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Paid Bookings</th>
                                <th class="text-end">Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffBreakdown as $row)
                                <tr>
                                    <td>{{ $row->staff_name }}</td>
                                    <td>{{ $row->paid_bookings }}</td>
                                    <td class="text-end fw-semibold">&#8369;{{ number_format((float) $row->revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-secondary">No staff sales data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="admin-report-shell p-2 p-lg-3 h-100">
                <div class="d-flex justify-content-between align-items-center px-2 pt-1 mb-2">
                    <h2 class="h5 mb-0">Recent Paid Transactions</h2>
                </div>
                <div class="table-responsive">
                    <table class="table admin-report-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Paid At</th>
                                <th>Booking</th>
                                <th>Method</th>
                                <th>Assigned Staff</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($sale->paid_at)->format('M d, Y h:i A') }}</td>
                                    <td>#{{ $sale->booking_id }}</td>
                                    <td>{{ $methodLabel((string) $sale->method) }}</td>
                                    <td>{{ trim((string) ($sale->assigned_staff_name ?? '')) !== '' ? $sale->assigned_staff_name : 'Unassigned' }}</td>
                                    <td class="text-end fw-semibold">&#8369;{{ number_format((float) $sale->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">No paid transactions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

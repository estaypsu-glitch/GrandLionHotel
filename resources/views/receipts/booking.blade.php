<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Receipt #{{ $booking->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 26px;
        }
        .header {
            border-bottom: 2px solid #b89254;
            margin-bottom: 16px;
            padding-bottom: 8px;
        }
        .brand {
            font-size: 22px;
            font-weight: 700;
            color: #7a5b2c;
        }
        .muted {
            color: #6b7280;
        }
        .reference-box {
            margin: 8px 0 12px;
            padding: 10px 12px;
            border: 1px solid #d8c7a7;
            background: #fff8eb;
            border-radius: 8px;
        }
        .reference-label {
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #7a5b2c;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .reference-value {
            font-size: 15px;
            letter-spacing: 0.04em;
            color: #1f2937;
            font-weight: 800;
            font-family: "Courier New", monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f9fafb;
            width: 30%;
        }
        .total {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }
    </style>
</head>
<body>
    @php
        $roomType = $booking->room->type ?? 'N/A';
        $roomView = $booking->room->view_type ?? 'Not specified';
        $paidAmount = (float) ($booking->payment?->amount ?? $booking->total_price);
        $originalAmount = (float) ($booking->payment?->original_amount ?? $booking->total_price);
        $discountType = (string) data_get($booking->reservation_meta, 'discount_type', '');
        $discountAmount = (float) ($booking->payment?->discount_amount ?? 0);
        $billedUnits = max(1, $booking->nights());
        $unitLabel = 'night';
        $bookedSubtotal = (float) $booking->total_price;
        $unitRate = $billedUnits > 0 ? round($bookedSubtotal / $billedUnits, 2) : $bookedSubtotal;
        $hasDiscount = $discountType !== '' && $discountAmount > 0;
        $transactionReference = strtoupper(trim((string) ($booking->payment?->transaction_reference ?? '')));
        $qrReference = strtoupper(trim((string) ($booking->payment?->qr_reference ?? '')));
    @endphp

    <div class="header">
        <div class="brand">The Grand Lion Hotel</div>
        <div class="muted">Official Booking Receipt</div>
    </div>

    <p><strong>Receipt Date:</strong> {{ now()->format('M d, Y h:i A') }}</p>
    <p><strong>Booking #:</strong> {{ $booking->id }}</p>
    <p><strong>Guest:</strong> {{ $booking->guestName() }}</p>
    <div class="reference-box">
        <div class="reference-label">Transaction Reference</div>
        <div class="reference-value">{{ $transactionReference !== '' ? $transactionReference : 'NOT AVAILABLE' }}</div>
    </div>
    @if($booking->guestEmail() !== '-')
        <p><strong>Email:</strong> {{ $booking->guestEmail() }}</p>
    @endif
    @if($booking->guestPhone() !== '-')
        <p><strong>Phone:</strong> {{ $booking->guestPhone() }}</p>
    @endif

    <table>
        <tr>
            <th>Room</th>
            <td>{{ $booking->room->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Room Type</th>
            <td>{{ $roomType }}</td>
        </tr>
        <tr>
            <th>Room View</th>
            <td>{{ $roomView }}</td>
        </tr>
        <tr>
            <th>Check-in</th>
            <td>{{ $booking->check_in->format('M d, Y') }}</td>
        </tr>
        <tr>
            <th>Check-out</th>
            <td>{{ $booking->check_out->format('M d, Y') }}</td>
        </tr>
        <tr>
            <th>Stay Type</th>
            <td>Nightly</td>
        </tr>
        <tr>
            <th>Nights</th>
            <td>{{ $billedUnits }}</td>
        </tr>
        <tr>
            <th>Guests</th>
            <td>{{ $booking->guests }}</td>
        </tr>
        <tr>
            <th>Booking Status</th>
            <td>{{ ucfirst($booking->status) }}</td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td>{{ \App\Models\Payment::methodLabel($booking->payment?->method) }}</td>
        </tr>
        <tr>
            <th>Payment Status</th>
            <td>{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</td>
        </tr>
        <tr>
            <th>Transaction Reference</th>
            <td>{{ $transactionReference !== '' ? $transactionReference : 'N/A' }}</td>
        </tr>
        @if($qrReference !== '')
            <tr>
                <th>QR Reference</th>
                <td>{{ $qrReference }}</td>
            </tr>
        @endif
        <tr>
            <th>Paid At</th>
            <td>{{ optional($booking->payment?->paid_at)->format('M d, Y h:i A') ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Nightly Rate (Booked)</th>
            <td>&#8369;{{ number_format($unitRate, 2) }}</td>
        </tr>
        <tr>
            <th>Subtotal Calculation</th>
            <td>&#8369;{{ number_format($unitRate, 2) }} x {{ $billedUnits }} {{ $unitLabel }}{{ $billedUnits === 1 ? '' : 's' }} = &#8369;{{ number_format($bookedSubtotal, 2) }}</td>
        </tr>
        @if($hasDiscount)
            <tr>
                <th>Original Amount</th>
                <td>&#8369;{{ number_format($originalAmount, 2) }}</td>
            </tr>
            <tr>
                <th>Discount</th>
                <td>{{ strtoupper($discountType) }} (&#8369;{{ number_format($discountAmount, 2) }})</td>
            </tr>
        @endif
        <tr>
            <th>Final Amount Paid</th>
            <td class="total">&#8369;{{ number_format($paidAmount, 2) }}</td>
        </tr>
        <tr>
            <th>Assigned Staff</th>
            <td>{{ $booking->assignedStaff?->name ?? 'N/A' }}</td>
        </tr>
    </table>

    <p class="muted" style="margin-top:18px;">
        Thank you for choosing The Grand Lion Hotel.
    </p>
</body>
</html>

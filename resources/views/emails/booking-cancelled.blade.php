<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Cancelled</title>
</head>
<body style="margin:0;padding:0;background:#f8f5ef;font-family:Arial,sans-serif;color:#1f2937;">
    <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="max-width:580px;background:#ffffff;border:1px solid #e8dece;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;background:linear-gradient(120deg,#c8a364,#a37b3f);color:#ffffff;">
                            <h1 style="margin:0;font-size:21px;line-height:1.2;">The Grand Lion Hotel Booking Update</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="margin:0 0 10px 0;">Hello {{ $booking->guestName() }},</p>
                            <p style="margin:0 0 16px 0;line-height:1.6;">
                                Booking <strong>#{{ $booking->id }}</strong> has been cancelled.
                            </p>
                            <p style="margin:0 0 8px 0;"><strong>Room:</strong> {{ $booking->room->name ?? 'N/A' }}</p>
                            <p style="margin:0 0 8px 0;"><strong>Stay:</strong> {{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</p>
                            <p style="margin:0 0 16px 0;"><strong>Payment status:</strong> {{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</p>
                            <p style="margin:0 0 16px 0;">
                                <a href="{{ route('bookings.show', $booking) }}" style="display:inline-block;padding:10px 16px;background:#b89254;color:#ffffff;text-decoration:none;border-radius:999px;font-weight:700;">
                                    View booking details
                                </a>
                            </p>

                            <p style="margin:0;line-height:1.6;color:#6b7280;">
                                If your payment was already completed, our team will process the refund based on policy.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

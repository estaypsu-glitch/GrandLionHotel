<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="margin:0;padding:0;background:#f8f5ef;font-family:Arial,sans-serif;color:#1f2937;">
    <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="max-width:580px;background:#ffffff;border:1px solid #e8dece;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;background:linear-gradient(120deg,#c8a364,#a37b3f);color:#ffffff;">
                            <h1 style="margin:0;font-size:21px;line-height:1.2;">The Grand Lion Hotel Account Confirmation</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="margin:0 0 10px 0;">Hello {{ $recipientName }},</p>
                            <p style="margin:0 0 18px 0;line-height:1.6;">
                                Use this 6-digit confirmation code to complete your registration:
                            </p>
                            <p style="margin:0 0 18px 0;font-size:32px;letter-spacing:8px;font-weight:700;color:#7a5b2c;">
                                {{ $code }}
                            </p>
                            <p style="margin:0 0 8px 0;line-height:1.6;">
                                This code will expire in <strong>2 minutes</strong>.
                            </p>
                            <p style="margin:0;line-height:1.6;color:#6b7280;">
                                If you did not request this, you can ignore this message.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

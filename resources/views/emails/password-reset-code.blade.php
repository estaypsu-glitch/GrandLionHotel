<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Reset Your Password</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f7f3ec; color: #1f2530; line-height: 1.6; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #fff 0%, #fbf9f5 100%); border-radius: 24px; overflow: hidden; box-shadow: 0 32px 64px rgba(16,24,40,0.12); }
        .email-header { background: linear-gradient(135deg, #b89254 0%, #ad8344 50%, #92713c 100%); padding: 2.5rem 2rem; text-align: center; }
        .logo { width: 80px; height: 80px; object-fit: contain; margin-bottom: 1rem; }
        .email-hero-text { font-family: 'Georgia', serif; font-size: 1.6rem; font-weight: 600; color: white; margin: 0 0 0.5rem 0; letter-spacing: -0.02em; }
        .email-subtext { color: rgba(255,255,255,0.9); font-size: 1rem; margin: 0; }
        .email-body { padding: 3rem 2.5rem; text-align: center; }
        .code-container { background: linear-gradient(135deg, #fff 0%, #fdfdfc 100%); border: 3px solid #e8ddcd; border-radius: 20px; padding: 2.5rem 1rem; margin: 2rem 0; box-shadow: 0 20px 40px rgba(184,146,84,0.15); }
        .code-display { font-family: 'Courier New', monospace; font-size: 3.2rem; font-weight: 900; letter-spacing: 0.6rem; color: #b89254; text-shadow: 0 2px 4px rgba(184,146,84,0.3); }
        .code-label { font-size: 0.95rem; color: #6f7785; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-bottom: 1rem; }
        .code-meta { color: #6f7785; font-size: 0.92rem; margin: 1.5rem 0; }
        .email-footer { background: #f8f4ed; padding: 2rem; text-align: center; border-top: 1px solid #e8ddcd; }
        .footer-text { color: #6f7785; font-size: 0.88rem; }
        .hotel-link { color: #b89254; text-decoration: none; font-weight: 700; }
        .hotel-link:hover { text-decoration: underline; }
        @media (max-width: 600px) { .email-body { padding: 2rem 1.5rem; } .code-display { font-size: 2.4rem; letter-spacing: 0.4rem; } }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <img src="{{ asset('brand/lion_logo.png') }}" alt="The Grand Lion Hotel" class="logo" style="display: block;">
            <h1 class="email-hero-text">Password Reset Request</h1>
            <p class="email-subtext">Securely reset your account access</p>
        </div>
        
        <div class="email-body">
            <h2 style="font-size: 1.5rem; margin-bottom: 1rem; font-family: 'Georgia', serif;">Your verification code</h2>
            <p style="color: #6f7785; font-size: 1.1rem; margin-bottom: 2rem;">Enter this one-time code on the password reset page. Code expires in <strong>10 minutes</strong>.</p>
            
            <div class="code-container">
                <div class="code-label">Your Code</div>
                <div class="code-display">{{ $code }}</div>
            </div>
            
            <div class="code-meta">
                <p>Didn't request this? <strong>Ignore this email.</strong></p>
                <p>Your account remains secure and unchanged.</p>
            </div>
        </div>
        
        <div class="email-footer">
            <p class="footer-text">
                Need help? <a href="{{ route('home') }}" class="hotel-link">Visit The Grand Lion Hotel</a><br>
                &copy; {{ now()->year }} The Grand Lion Hotel. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

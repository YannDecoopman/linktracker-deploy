<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 40px 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 40px; border: 1px solid #e5e5e5;">
        <h2 style="margin: 0 0 16px; color: #171717; font-size: 20px;">Link Tracker Login</h2>
        <p style="color: #525252; line-height: 1.6; margin: 0 0 24px;">Click the button below to sign in. This link expires in 15 minutes.</p>
        <a href="{{ $url }}" style="display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 500;">Sign in</a>
        <p style="color: #a3a3a3; font-size: 13px; margin: 24px 0 0; line-height: 1.5;">If the button doesn't work, copy this link:<br>{{ $url }}</p>
    </div>
</body>
</html>

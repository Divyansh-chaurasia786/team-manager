<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings Verification Code</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 30px; color: #1e293b;">
    <div style="max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #4338ca, #312e81); padding: 30px; text-align: center; color: #ffffff;">
            <div style="font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">EcoFone App</div>
            <div style="font-size: 12px; color: #c7d2fe; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Security Verification</div>
        </div>

        <!-- Body -->
        <div style="padding: 30px;">
            <p style="font-size: 15px; line-height: 1.6; margin-top: 0;">
                Hello <strong>{{ $user->name }}</strong>,
            </p>
            <p style="font-size: 14px; line-height: 1.6; color: #475569;">
                A request was made to update sensitive information on your account:
            </p>

            <ul style="font-size: 13px; color: #334155; line-height: 1.6; margin-bottom: 25px; padding-left: 20px;">
                @foreach($changedFields as $field)
                    <li><strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong></li>
                @endforeach
            </ul>

            <div style="background: #f1f5f9; border-radius: 16px; padding: 25px; text-align: center; border: 1px dashed #cbd5e1; margin-bottom: 25px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 8px;">Your One-Time Password (OTP)</div>
                <div style="font-size: 32px; font-weight: 900; letter-spacing: 6px; color: #4338ca; font-family: monospace;">{{ $otp }}</div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 8px;">This code will expire in 10 minutes.</div>
            </div>

            <p style="font-size: 12px; line-height: 1.6; color: #64748b;">
                If you did not request this update, please contact your Team Lead immediately to secure your account.
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8;">
            &copy; {{ date('Y') }} EcoFone Technologies. Secure Workforce Platform.
        </div>
    </div>
</body>
</html>
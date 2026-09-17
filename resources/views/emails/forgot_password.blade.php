<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Verification Code</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 30px; color: #1e293b;">
    <div style="max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #e11d48, #9f1239); padding: 30px; text-align: center; color: #ffffff;">
            <div style="font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">EcoFone App</div>
            <div style="font-size: 12px; color: #fecdd3; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Password Reset Request</div>
        </div>

        <!-- Body -->
        <div style="padding: 30px;">
            <p style="font-size: 15px; line-height: 1.6; margin-top: 0;">
                Hello <strong>{{ $user->name }}</strong>,
            </p>
            <p style="font-size: 14px; line-height: 1.6; color: #475569;">
                We received a request to reset the password for your EcoFone account (<strong>{{ $user->email }}</strong>).
            </p>

            <div style="background: #fff1f2; border-radius: 16px; padding: 25px; text-align: center; border: 1px dashed #f43f5e; margin: 25px 0;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9f1239; margin-bottom: 8px;">Password Reset OTP</div>
                <div style="font-size: 34px; font-weight: 900; letter-spacing: 8px; color: #e11d48; font-family: monospace;">{{ $otp }}</div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 8px;">This code is valid for 30 minutes.</div>
            </div>

            <p style="font-size: 13px; line-height: 1.6; color: #475569;">
                Enter this code on the password reset page along with your new password. All of your historical tasks, attendance records, and team assignments remain completely intact and secure.
            </p>

            <p style="font-size: 12px; line-height: 1.6; color: #94a3b8; margin-top: 25px;">
                If you did not request a password reset, you can safely ignore this email. Your password will not change unless the code is entered.
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8;">
            &copy; {{ date('Y') }} EcoFone Technologies. Secure Workforce Platform.
        </div>
    </div>
</body>
</html>

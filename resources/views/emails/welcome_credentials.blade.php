<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to EcoFone App</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); padding: 32px 24px; text-align: center; color: #ffffff; }
        .content { padding: 32px 24px; }
        .cred-box { background: #f1f5f9; border-radius: 12px; padding: 20px; border: 1px solid #cbd5e1; margin: 24px 0; }
        .cred-item { margin-bottom: 12px; }
        .cred-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.5px; }
        .cred-value { font-size: 15px; font-weight: 700; color: #0f172a; font-family: monospace; }
        .btn { display: inline-block; padding: 14px 28px; background: #4f46e5; color: #ffffff !important; text-decoration: none; font-weight: 700; border-radius: 10px; text-align: center; font-size: 14px; margin-top: 10px; }
        .footer { padding: 20px 24px; background: #f8fafc; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px; font-weight: 900;">EcoFone <span style="color: #f97316;">App</span></h1>
            <p style="margin: 6px 0 0 0; font-size: 13px; color: #cbd5e1;">Enterprise Team Operations & Deliverables Portal</p>
        </div>
        <div class="content">
            <p style="font-size: 15px; line-height: 1.6; margin-top: 0;">
                Hello <strong>{{ $user->name }}</strong>,
            </p>
            <p style="font-size: 13px; color: #475569; line-height: 1.6;">
                Welcome to the EcoFone operations team. You have been registered as <strong>{{ $user->designation ?? 'Team Member' }}</strong>.
                Your account has been created with a <strong>One-Time Temporary Password</strong>.
            </p>

            <div class="cred-box">
                <div class="cred-item">
                    <div class="cred-label">Login URL</div>
                    <div class="cred-value" style="font-family: sans-serif; font-size: 13px; color: #4f46e5;">{{ url('/login') }}</div>
                </div>
                <div class="cred-item">
                    <div class="cred-label">Unique Username (For Sign In)</div>
                    <div class="cred-value" style="color: #4f46e5; font-size: 16px;">{{ $user->username }}</div>
                </div>
                <div class="cred-item">
                    <div class="cred-label">Work Email Address</div>
                    <div class="cred-value">{{ $user->email }}</div>
                </div>
                <div class="cred-item">
                    <div class="cred-label">Registered Mobile</div>
                    <div class="cred-value">{{ $user->mobile_number }}</div>
                </div>
                <div class="cred-item" style="margin-bottom: 0;">
                    <div class="cred-label">One-Time Password (OTP)</div>
                    <div class="cred-value" style="color: #4f46e5; font-size: 18px; letter-spacing: 1px;">{{ $plainPassword }}</div>
                </div>
            </div>

            <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: 12px 16px; margin-bottom: 24px;">
                <p style="margin: 0; font-size: 12px; color: #92400e; font-weight: 600;">
                    ⚠️ <strong>Security Notice:</strong> Upon your first sign-in, the system will strictly require you to change this temporary password to your own permanent, private password before you can proceed.
                </p>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/login') }}" class="btn">Sign In to Dashboard &rarr;</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} EcoFone Technologies &bull; Luxury within reach &bull; Confidential
        </div>
    </div>
</body>
</html>
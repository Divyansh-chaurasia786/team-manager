<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leave Management Notification - EcoFone Operations</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); padding: 28px 24px; text-align: center; color: #ffffff; }
        .content { padding: 28px 24px; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-special { background: #f3e8ff; color: #6b21a8; }
        .detail-box { background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #cbd5e1; margin: 20px 0; }
        .detail-item { margin-bottom: 12px; }
        .detail-item:last-child { margin-bottom: 0; }
        .detail-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.5px; }
        .detail-value { font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px; }
        .remarks-box { background: #ffffff; border-left: 4px solid #4f46e5; border-radius: 6px; padding: 12px; font-size: 13px; color: #334155; line-height: 1.5; margin-top: 6px; }
        .btn { display: inline-block; padding: 12px 24px; background: #4f46e5; color: #ffffff !important; text-decoration: none; font-weight: 700; border-radius: 10px; text-align: center; font-size: 13px; margin-top: 10px; }
        .footer { padding: 18px 24px; background: #f8fafc; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 20px; font-weight: 900;">EcoFone <span style="color: #818cf8;">Operations</span></h1>
            <p style="margin: 4px 0 0 0; font-size: 12px; color: #c7d2fe;">
                Leave & Absence Management Notification
            </p>
        </div>

        <div class="content">
            <div style="margin-bottom: 16px;">
                @if($eventType === 'approved')
                    <span class="badge badge-success">Approved &bull; Attendance Synchronized</span>
                @elseif($eventType === 'ceo_granted')
                    <span class="badge badge-special">CEO Special Grant &bull; Negative Balance Allowed</span>
                @elseif($eventType === 'rejected')
                    <span class="badge badge-danger">Application Rejected</span>
                @else
                    <span class="badge badge-warning">New Application &bull; Pending Review</span>
                @endif
            </div>

            <p style="font-size: 14px; line-height: 1.6; margin-top: 0;">
                Hello <strong>{{ $leave->user?->name ?? 'Team Member' }}</strong>,
            </p>

            <p style="font-size: 13px; color: #475569; line-height: 1.6;">
                @if($eventType === 'approved')
                    Your request for <strong>{{ $leave->leave_type_label }}</strong> has been approved by HR. Your attendance records have been automatically marked as <em>On Leave</em> for the requested duration.
                @elseif($eventType === 'ceo_granted')
                    Your request for <strong>{{ $leave->leave_type_label }}</strong> has been <strong>granted by executive exception (CEO)</strong> without available quota. Your leave balance has been adjusted into negative balance accordingly.
                @elseif($eventType === 'rejected')
                    Your request for <strong>{{ $leave->leave_type_label }}</strong> was not approved. If not present at work on these dates, attendance will reflect as Absent.
                @else
                    A new leave application has been submitted by <strong>{{ $leave->user?->name }}</strong> ({{ strtoupper($leave->user?->role ?? 'member') }}) and is awaiting HR evaluation.
                @endif
            </p>

            <div class="detail-box">
                <div class="detail-item">
                    <div class="detail-label">Leave Category</div>
                    <div class="detail-value" style="color: #312e81;">{{ $leave->leave_type_label }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Duration & Dates</div>
                    <div class="detail-value">
                        {{ $leave->start_date->format('d M, Y') }} &rarr; {{ $leave->end_date->format('d M, Y') }}
                        <span style="color: #6366f1; font-size: 12px; margin-left: 6px;">({{ $leave->total_days }} day{{ $leave->total_days > 1 ? 's' : '' }})</span>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Reason Stated</div>
                    <div class="detail-value" style="font-weight: 500; font-size: 13px; color: #334155;">{{ $leave->reason }}</div>
                </div>

                @if($leave->reviewed_by)
                    <div class="detail-item">
                        <div class="detail-label">Reviewed By</div>
                        <div class="detail-value">{{ $leave->reviewer?->name ?? 'Leadership Desk' }} ({{ strtoupper($leave->reviewer?->role ?? 'HR') }})</div>
                    </div>
                @endif

                @if(!empty($remarks))
                    <div class="detail-item">
                        <div class="detail-label">Supervisor / HR Remarks</div>
                        <div class="remarks-box">{{ $remarks }}</div>
                    </div>
                @endif
            </div>

            @if($leaveSummary)
                <div style="background: #f1f5f9; border-radius: 10px; padding: 14px; margin-bottom: 20px;">
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 8px;">
                        Remaining Leave Balance Overview
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                        <span>Casual Leaves Remaining:</span>
                        <strong>{{ $leaveSummary['casual_remaining'] }} / {{ $leaveSummary['casual_quota'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                        <span>Sick Leaves Remaining:</span>
                        <strong>{{ $leaveSummary['sick_remaining'] }} / {{ $leaveSummary['sick_quota'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                        <span>Total Leave Balance:</span>
                        <strong style="{{ $leaveSummary['total_remaining'] < 0 ? 'color: #e11d48;' : 'color: #16a34a;' }}">
                            {{ $leaveSummary['total_remaining'] }} days
                            @if($leaveSummary['total_remaining'] < 0)
                                (Negative / Overdraft)
                            @endif
                        </strong>
                    </div>
                </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ url('/leaves') }}" class="btn">View in EcoFone Workspace &rarr;</a>
            </div>
        </div>

        <div class="footer">
            EcoFone Internal Operations Platform &bull; Automated Leave Synchronization Engine<br>
            Please do not reply directly to this automated email.
        </div>
    </div>
</body>
</html>

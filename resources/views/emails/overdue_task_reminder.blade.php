<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Urgent Task Notice - EcoFone Operations</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%); padding: 32px 24px; text-align: center; color: #ffffff; }
        .content { padding: 32px 24px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .detail-box { background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #cbd5e1; margin: 20px 0; }
        .detail-item { margin-bottom: 12px; }
        .detail-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.5px; }
        .detail-value { font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px; }
        .desc-box { background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; font-size: 13px; color: #334155; line-height: 1.6; margin-top: 4px; }
        .btn { display: inline-block; padding: 14px 28px; background: #dc2626; color: #ffffff !important; text-decoration: none; font-weight: 700; border-radius: 10px; text-align: center; font-size: 14px; margin-top: 10px; }
        .footer { padding: 20px 24px; background: #f8fafc; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 22px; font-weight: 900;">EcoFone <span style="color: #f87171;">Operations</span></h1>
            <p style="margin: 6px 0 0 0; font-size: 13px; color: #fecaca;">Formal Overdue Deliverable Notification</p>
        </div>
        <div class="content">
            <div style="margin-bottom: 16px;">
                <span class="badge badge-danger">Action Required &bull; Overdue</span>
            </div>

            <p style="font-size: 15px; line-height: 1.6; margin-top: 0;">
                Dear <strong>{{ $task->assignedTo->name }}</strong>,
            </p>
            <p style="font-size: 13px; color: #475569; line-height: 1.6;">
                This formal notification is generated to bring to your immediate attention that the following deliverable assigned to you has passed its scheduled deadline without completion or submission.
            </p>

            <div class="detail-box">
                <div class="detail-item">
                    <div class="detail-label">Task Title</div>
                    <div class="detail-value" style="font-size: 15px; color: #b91c1c;">{{ $task->title }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Assigned By (Supervisor)</div>
                    <div class="detail-value">{{ $task->assignedBy->name }} ({{ strtoupper($task->assignedBy->role) }}) &bull; {{ $task->assignedBy->email }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Scheduled Deadline</div>
                    <div class="detail-value" style="color: #dc2626;">{{ $task->deadline->format('l, d F Y - h:i A') }} ({{ $task->deadline->diffForHumans() }})</div>
                </div>

                @if($task->reassignment_count > 0 && $task->previous_deadline)
                <div class="detail-item">
                    <div class="detail-label">Original Baseline Deadline</div>
                    <div class="detail-value" style="color: #64748b;">{{ $task->previous_deadline->format('d M Y - h:i A') }} (Reassigned {{ $task->reassignment_count }} times)</div>
                </div>
                @endif

                <div class="detail-item">
                    <div class="detail-label">Current Status</div>
                    <div class="detail-value">{{ ucfirst($task->status) }}</div>
                </div>

                @if($task->description)
                <div class="detail-item">
                    <div class="detail-label">Scope & Requirements</div>
                    <div class="desc-box">{{ $task->description }}</div>
                </div>
                @endif

                @if($task->revision_notes)
                <div class="detail-item">
                    <div class="detail-label" style="color: #b45309;">Revision / Feedback Notes</div>
                    <div class="desc-box" style="background: #fffbeb; border-color: #fde68a; color: #92400e;">{{ $task->revision_notes }}</div>
                </div>
                @endif
            </div>

            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px;">
                <p style="margin: 0; font-size: 12px; color: #991b1b; line-height: 1.6;">
                    ⚠️ <strong>Immediate Protocol:</strong> Please access your team workspace, complete the work, and submit the deliverable link or file immediately. If you encounter any blockers, please submit an update on the task thread to inform your Team Lead.
                </p>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/tasks') }}" class="btn">View & Submit Deliverable &rarr;</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} EcoFone Technologies &bull; Operations & SLA Compliance Notification &bull; Confidential
        </div>
    </div>
</body>
</html>
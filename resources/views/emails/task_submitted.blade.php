<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Task Deliverables Submitted for Review - EcoFone Operations</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%); padding: 32px 24px; text-align: center; color: #ffffff; }
        .content { padding: 32px 24px; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .detail-box { background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #cbd5e1; margin: 20px 0; }
        .detail-item { margin-bottom: 14px; }
        .detail-item:last-child { margin-bottom: 0; }
        .detail-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.5px; }
        .detail-value { font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px; }
        .desc-box { background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; font-size: 13px; color: #334155; line-height: 1.6; margin-top: 4px; }
        .remarks-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; font-size: 13px; color: #166534; line-height: 1.6; margin-top: 4px; font-style: italic; }
        .btn { display: inline-block; padding: 14px 28px; background: #059669; color: #ffffff !important; text-decoration: none; font-weight: 700; border-radius: 10px; text-align: center; font-size: 14px; margin-top: 10px; box-shadow: 0 2px 4px rgba(5, 150, 105, 0.3); }
        .footer { padding: 20px 24px; background: #f8fafc; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 22px; font-weight: 900;">EcoFone <span style="color: #6ee7b7;">Operations</span></h1>
            <p style="margin: 6px 0 0 0; font-size: 13px; color: #a7f3d0;">
                Task Deliverable Submitted for Team Lead Review
            </p>
        </div>
        <div class="content">
            <div style="margin-bottom: 16px;">
                <span class="badge badge-success">Deliverable Submitted &bull; Action Required</span>
            </div>

            <p style="font-size: 15px; line-height: 1.6; margin-top: 0;">
                Dear <strong>{{ $tl->name }}</strong>,
            </p>
            <p style="font-size: 13px; color: #475569; line-height: 1.6;">
                <strong>{{ $task->assignedTo->name }}</strong> has completed and submitted the deliverables for task <strong>"{{ $task->title }}"</strong>. Please inspect the submission details below and proceed to your dashboard to complete or request revisions.
            </p>

            <div class="detail-box">
                <div class="detail-item">
                    <div class="detail-label">Task Title</div>
                    <div class="detail-value" style="font-size: 16px; color: #065f46;">{{ $task->title }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Submitted By (Team Member)</div>
                    <div class="detail-value">{{ $task->assignedTo->name }} &bull; {{ $task->assignedTo->email }}</div>
                    <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px;">{{ $task->assignedTo->designation ?? 'Team Member' }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Submission Timestamp</div>
                    <div class="detail-value" style="color: #065f46;">
                        {{ $task->submitted_at ? $task->submitted_at->format('l, d M Y - h:i A') : now()->format('l, d M Y - h:i A') }}
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Target Deadline</div>
                    <div class="detail-value" style="color: #dc2626;">
                        {{ $task->deadline ? $task->deadline->format('l, d M Y - h:i A') : 'N/A' }}
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Punctuality Status</div>
                    <div class="detail-value">
                        @if($task->submitted_at && $task->deadline && $task->submitted_at->lte($task->deadline))
                            <span style="color: #16a34a; font-weight: 800;">✓ Submitted On Time</span>
                        @else
                            <span style="color: #d97706; font-weight: 800;">⚠ Submitted Post-Deadline</span>
                        @endif
                    </div>
                </div>

                @if($task->submission_remarks)
                <div class="detail-item">
                    <div class="detail-label" style="color: #065f46;">Employee Submission Remarks / Notes</div>
                    <div class="remarks-box">
                        "{{ $task->submission_remarks }}"
                    </div>
                </div>
                @endif

                @if($task->submission_link)
                <div class="detail-item">
                    <div class="detail-label">Deliverable URL / Link</div>
                    <div class="detail-value">
                        <a href="{{ $task->submission_link }}" target="_blank" style="color: #2563eb; text-decoration: underline; word-break: break-all;">
                            {{ $task->submission_link }}
                        </a>
                    </div>
                </div>
                @endif

                @if($task->submission_file)
                <div class="detail-item">
                    <div class="detail-label">Attached File</div>
                    <div class="detail-value" style="color: #334155;">
                        📁 {{ basename($task->submission_file) }} ({{ strtoupper($task->submission_file_type ?? 'File') }})
                    </div>
                </div>
                @endif

                @if($task->description)
                <div class="detail-item">
                    <div class="detail-label">Original Task Scope</div>
                    <div class="desc-box">{{ $task->description }}</div>
                </div>
                @endif
            </div>

            <div style="text-align: center; margin: 28px 0 10px 0;">
                <a href="{{ url('/tl/dashboard') }}" class="btn">
                    Review Deliverables in Dashboard &rarr;
                </a>
            </div>

            <p style="font-size: 12px; color: #64748b; text-align: center; margin-top: 18px;">
                You can mark this task as Completed or request revisions directly from your Team Lead Dashboard.
            </p>
        </div>
        <div class="footer">
            EcoFone Team Management Platform &bull; Automated notification sent to assigned Team Lead.
        </div>
    </div>
</body>
</html>

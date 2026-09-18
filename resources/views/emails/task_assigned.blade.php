<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $isReassignment ? 'Task Revision Notification' : 'New Task Assigned' }} - EcoFone Operations</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); padding: 32px 24px; text-align: center; color: #ffffff; }
        .content { padding: 32px 24px; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-primary { background: #e0e7ff; color: #3730a3; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .detail-box { background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #cbd5e1; margin: 20px 0; }
        .detail-item { margin-bottom: 14px; }
        .detail-item:last-child { margin-bottom: 0; }
        .detail-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.5px; }
        .detail-value { font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px; }
        .desc-box { background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; font-size: 13px; color: #334155; line-height: 1.6; margin-top: 4px; }
        .btn { display: inline-block; padding: 14px 28px; background: #4f46e5; color: #ffffff !important; text-decoration: none; font-weight: 700; border-radius: 10px; text-align: center; font-size: 14px; margin-top: 10px; box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3); }
        .footer { padding: 20px 24px; background: #f8fafc; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 22px; font-weight: 900;">EcoFone <span style="color: #818cf8;">Operations</span></h1>
            <p style="margin: 6px 0 0 0; font-size: 13px; color: #c7d2fe;">
                {{ $isReassignment ? 'Deliverable Revision & New Deadline Directives' : 'New Task Deliverable Assignment' }}
            </p>
        </div>
        <div class="content">
            <div style="margin-bottom: 16px;">
                @if($isReassignment)
                    <span class="badge badge-amber">Revision Requested &bull; Update Required</span>
                @else
                    <span class="badge badge-primary">Deliverable Assigned &bull; Action Required</span>
                @endif
            </div>

            <p style="font-size: 15px; line-height: 1.6; margin-top: 0;">
                Dear <strong>{{ $task->assignedTo->name }}</strong>,
            </p>
            <p style="font-size: 13px; color: #475569; line-height: 1.6;">
                @if($isReassignment)
                    Your Team Lead has reviewed your submission and requested revisions on the deliverable below. Please review the updated deadline and feedback.
                @else
                    A new work deliverable has been assigned to you in the EcoFone team workspace. Please review the requirements, schedule your workflow, and submit before the designated deadline.
                @endif
            </p>

            <div class="detail-box">
                <div class="detail-item">
                    <div class="detail-label">Task Title</div>
                    <div class="detail-value" style="font-size: 16px; color: #312e81;">{{ $task->title }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Assigned By (Supervisor)</div>
                    <div class="detail-value">{{ $task->assignedBy->name }} ({{ strtoupper($task->assignedBy->role) }}) &bull; {{ $task->assignedBy->email }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Assigned Time</div>
                    <div class="detail-value" style="color: #475569;">{{ $task->created_at->format('l, d M Y - h:i A') }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Target Deadline</div>
                    <div class="detail-value" style="color: #dc2626;">{{ $task->deadline->format('l, d M Y - h:i A') }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Time Remaining / Due Status</div>
                    <div class="detail-value" style="color: #4f46e5;">
                        {{ $task->due_label }}
                    </div>
                </div>

                @if($isReassignment && $task->previous_deadline)
                <div class="detail-item">
                    <div class="detail-label">Previous Deadline</div>
                    <div class="detail-value" style="color: #64748b; text-decoration: line-through;">{{ $task->previous_deadline->format('d M Y - h:i A') }} (Revision #{{ $task->reassignment_count }})</div>
                </div>
                @endif

                @if($task->description)
                <div class="detail-item">
                    <div class="detail-label">Task Scope & Instructions</div>
                    <div class="desc-box">{{ $task->description }}</div>
                </div>
                @endif

                @if($isReassignment && $task->revision_notes)
                <div class="detail-item">
                    <div class="detail-label" style="color: #b45309;">Revision & Feedback Directives</div>
                    <div class="desc-box" style="background: #fffbeb; border-color: #fde68a; color: #92400e;">{{ $task->revision_notes }}</div>
                </div>
                @endif
            </div>

            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px;">
                <p style="margin: 0; font-size: 12px; color: #166534; line-height: 1.6;">
                    💡 <strong>Workflow Tip:</strong> Keep your Team Lead updated by posting progress notes on your task thread. When completed, submit your deliverable link or file directly from your portal.
                </p>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/tasks') }}" class="btn">Open Workspace & View Task &rarr;</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} EcoFone Technologies &bull; Automated Operations & Task Delivery Platform
        </div>
    </div>
</body>
</html>

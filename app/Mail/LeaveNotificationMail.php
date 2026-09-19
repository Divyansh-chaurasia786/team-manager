<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LeaveApplication $leave,
        public string $eventType, // 'applied', 'approved', 'rejected', 'ceo_granted'
        public ?string $remarks = null,
        public ?array $leaveSummary = null
    ) {}

    public function envelope(): Envelope
    {
        $applicantName = $this->leave->user?->name ?? 'Team Member';
        $typeLabel = $this->leave->leave_type_label;
        $dateRange = $this->leave->start_date->format('d M') . ' to ' . $this->leave->end_date->format('d M, Y');

        $subject = match($this->eventType) {
            'approved'    => "✅ Leave Approved: {$typeLabel} ({$dateRange})",
            'ceo_granted' => "🌟 CEO Special Grant: {$typeLabel} Approved ({$dateRange})",
            'rejected'    => "❌ Leave Application Update: {$typeLabel} ({$dateRange})",
            'applied'     => "📋 New Leave Request: {$applicantName} - {$typeLabel} ({$dateRange})",
            default       => "Leave Update: {$typeLabel} ({$dateRange})",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave_notification',
        );
    }
}

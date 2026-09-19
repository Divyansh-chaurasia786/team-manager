<?php
namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public User $tl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'TASK SUBMITTED FOR REVIEW: %s • Submitted by %s',
                $this->task->title,
                $this->task->assignedTo?->name ?? 'Employee'
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.task_submitted',
        );
    }
}

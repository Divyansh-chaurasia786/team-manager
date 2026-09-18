<?php
namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public bool $isReassignment = false
    ) {}

    public function envelope(): Envelope
    {
        $prefix = $this->isReassignment ? 'TASK REVISION REQUIRED' : 'NEW TASK ASSIGNED';
        return new Envelope(
            subject: sprintf(
                '%s: %s • Due %s',
                $prefix,
                $this->task->title,
                $this->task->deadline ? $this->task->deadline->format('d M, h:i A') : 'TBD'
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.task_assigned',
        );
    }
}

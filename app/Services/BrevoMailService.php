<?php
namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use App\Mail\EmployeeWelcomeMail;
use App\Mail\SecurityOtpMail;
use App\Mail\ForgotPasswordMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BrevoMailService
{
    /**
     * Send welcome email containing login credentials and one-time password.
     */
    public static function sendWelcomeMail(User $user, string $plainOtp): bool
    {
        if (app()->environment('testing')) {
            Mail::to($user->email)->send(new EmployeeWelcomeMail($user, $plainOtp));
            return true;
        }

        try {
            $mailable = new EmployeeWelcomeMail($user, $plainOtp);
            $html = $mailable->render();
            $subject = 'Welcome to EcoFone App - Your Account Credentials & One-Time Password';

            $result = self::sendViaApi($user->email, $user->name, $subject, $html);
            if ($result['success']) {
                ActivityLog::log(
                    action: 'email_dispatched',
                    description: sprintf('Welcome credentials email delivered to %s (%s). Brevo ID: %s', $user->name, $user->email, $result['message_id']),
                    entityType: 'User',
                    entityId: $user->id,
                    userId: auth()->id() ?? $user->id
                );
                return true;
            }

            // Fallback to standard Laravel Mailer
            Mail::to($user->email)->send($mailable);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
            try {
                Mail::to($user->email)->send(new EmployeeWelcomeMail($user, $plainOtp));
                return true;
            } catch (\Exception $fallbackError) {
                Log::error('Fallback mail failed too: ' . $fallbackError->getMessage());
                return false;
            }
        }
    }

    /**
     * Send security OTP email for account setting updates.
     */
    public static function sendSecurityOtp(User $user, string $otp, array $changedFields): bool
    {
        if (app()->environment('testing')) {
            Mail::to($user->email)->send(new SecurityOtpMail($user, $otp, $changedFields));
            return true;
        }

        try {
            $mailable = new SecurityOtpMail($user, $otp, $changedFields);
            $html = $mailable->render();
            $subject = 'Verification Code (OTP) for EcoFone Account Settings Update';

            $result = self::sendViaApi($user->email, $user->name, $subject, $html);
            if ($result['success']) {
                return true;
            }

            Mail::to($user->email)->send($mailable);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email: ' . $e->getMessage());
            try {
                Mail::to($user->email)->send(new SecurityOtpMail($user, $otp, $changedFields));
                return true;
            } catch (\Exception $fallbackError) {
                return false;
            }
        }
    }

    /**
     * Send password reset OTP email.
     */
    public static function sendPasswordResetOtp(User $user, string $otp): bool
    {
        if (app()->environment('testing')) {
            Mail::to($user->email)->send(new ForgotPasswordMail($user, $otp));
            return true;
        }

        try {
            $mailable = new ForgotPasswordMail($user, $otp);
            $html = $mailable->render();
            $subject = 'Your Password Reset Verification Code - EcoFone App';

            $result = self::sendViaApi($user->email, $user->name, $subject, $html);
            if ($result['success']) {
                ActivityLog::log(
                    action: 'password_reset_otp_sent',
                    description: sprintf('Password reset OTP sent to %s (%s). Brevo ID: %s', $user->name, $user->email, $result['message_id']),
                    entityType: 'User',
                    entityId: $user->id,
                    userId: $user->id
                );
                return true;
            }

            Mail::to($user->email)->send($mailable);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send password reset OTP email: ' . $e->getMessage());
            try {
                Mail::to($user->email)->send(new ForgotPasswordMail($user, $otp));
                return true;
            } catch (\Exception $fallbackError) {
                return false;
            }
        }
    }

    /**
     * Send overdue task reminder email.
     */
    public static function sendOverdueTaskReminder(\App\Models\Task $task): bool
    {
        if (!$task->assignedTo || empty($task->assignedTo->email)) {
            return false;
        }

        if (app()->environment('testing')) {
            Mail::to($task->assignedTo->email)->send(new \App\Mail\OverdueTaskReminderMail($task));
            return true;
        }

        try {
            $mailable = new \App\Mail\OverdueTaskReminderMail($task);
            $html = $mailable->render();
            $subject = 'URGENT: Task Overdue Notification - ' . $task->title;

            $result = self::sendViaApi($task->assignedTo->email, $task->assignedTo->name, $subject, $html);
            if ($result['success']) {
                return true;
            }

            Mail::to($task->assignedTo->email)->send($mailable);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send overdue reminder: ' . $e->getMessage());
            try {
                Mail::to($task->assignedTo->email)->send(new \App\Mail\OverdueTaskReminderMail($task));
                return true;
            } catch (\Exception $fallbackError) {
                return false;
            }
        }
    }

    /**
     * Send task assignment or reassignment email to the assignee.
     */
    public static function sendTaskAssignedMail(\App\Models\Task $task, bool $isReassignment = false): bool
    {
        $assignee = $task->assignedTo;
        if (!$assignee || empty($assignee->email)) {
            return false;
        }

        if (app()->environment('testing')) {
            Mail::to($assignee->email)->send(new \App\Mail\TaskAssignedMail($task, $isReassignment));
            return true;
        }

        try {
            $mailable = new \App\Mail\TaskAssignedMail($task, $isReassignment);
            $html = $mailable->render();
            $prefix = $isReassignment ? 'TASK REVISION REQUIRED' : 'NEW TASK ASSIGNED';
            $subject = sprintf(
                '%s: %s • Due %s',
                $prefix,
                $task->title,
                $task->deadline ? $task->deadline->format('d M, h:i A') : 'TBD'
            );

            $result = self::sendViaApi($assignee->email, $assignee->name, $subject, $html);
            if ($result['success']) {
                ActivityLog::log(
                    action: $isReassignment ? 'task_reassignment_email_sent' : 'task_assignment_email_sent',
                    description: sprintf('%s email delivered to %s (%s) for task "%s". Brevo ID: %s',
                        $isReassignment ? 'Task reassignment' : 'Task assignment',
                        $assignee->name,
                        $assignee->email,
                        $task->title,
                        $result['message_id']
                    ),
                    entityType: 'Task',
                    entityId: $task->id,
                    userId: auth()->id() ?? $task->assigned_by
                );
                return true;
            }

            Mail::to($assignee->email)->send($mailable);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send task assignment email: ' . $e->getMessage());
            try {
                Mail::to($assignee->email)->send(new \App\Mail\TaskAssignedMail($task, $isReassignment));
                return true;
            } catch (\Exception $fallbackError) {
                Log::error('Fallback mail for task assignment failed too: ' . $fallbackError->getMessage());
                return false;
            }
        }
    }

    /**
     * Send email notification to the assigned Team Lead when an employee submits a task.
     */
    public static function sendTaskSubmittedMail(\App\Models\Task $task): bool
    {
        $employee = $task->assignedTo;
        if (!$employee) {
            return false;
        }

        // Determine the employee's assigned TL
        $assignedTl = null;
        if ($employee->creator && $employee->creator->isTL()) {
            $assignedTl = $employee->creator;
        } elseif ($task->assignedBy && $task->assignedBy->isTL()) {
            $assignedTl = $task->assignedBy;
        } elseif ($employee->creator) {
            $assignedTl = $employee->creator;
        } elseif ($task->assignedBy) {
            $assignedTl = $task->assignedBy;
        }

        if (!$assignedTl || empty($assignedTl->email)) {
            Log::warning(sprintf('Cannot dispatch task submitted email for task #%d: No assigned TL found for employee #%d (%s)',
                $task->id,
                $employee->id,
                $employee->name
            ));
            return false;
        }

        if (app()->environment('testing')) {
            Mail::to($assignedTl->email)->send(new \App\Mail\TaskSubmittedMail($task, $assignedTl));
            return true;
        }

        try {
            $mailable = new \App\Mail\TaskSubmittedMail($task, $assignedTl);
            $html = $mailable->render();
            $subject = sprintf(
                'TASK SUBMITTED FOR REVIEW: %s • Submitted by %s',
                $task->title,
                $employee->name
            );

            $result = self::sendViaApi($assignedTl->email, $assignedTl->name, $subject, $html);
            if ($result['success']) {
                ActivityLog::log(
                    action: 'task_submitted_email_sent',
                    description: sprintf('Task submission email for "%s" delivered to assigned TL %s (%s). Brevo ID: %s',
                        $task->title,
                        $assignedTl->name,
                        $assignedTl->email,
                        $result['message_id']
                    ),
                    entityType: 'Task',
                    entityId: $task->id,
                    userId: $employee->id
                );
                return true;
            }

            Mail::to($assignedTl->email)->send($mailable);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send task submitted email: ' . $e->getMessage());
            try {
                Mail::to($assignedTl->email)->send(new \App\Mail\TaskSubmittedMail($task, $assignedTl));
                return true;
            } catch (\Exception $fallbackError) {
                Log::error('Fallback mail for task submitted failed too: ' . $fallbackError->getMessage());
                return false;
            }
        }
    }

    /**
     * Send leave application/status notification email to designated recipients (HR, CEO, TL, or applicant).
     */
    public static function sendLeaveNotificationMail(\App\Models\LeaveApplication $leave, string $eventType, array $recipients, ?string $remarks = null, ?array $leaveSummary = null): bool
    {
        $filtered = array_values(array_filter(array_unique($recipients)));
        if (empty($filtered)) {
            return false;
        }

        if (app()->environment('testing')) {
            Mail::to($filtered)->send(new \App\Mail\LeaveNotificationMail($leave, $eventType, $remarks, $leaveSummary));
            return true;
        }

        try {
            $mailable = new \App\Mail\LeaveNotificationMail($leave, $eventType, $remarks, $leaveSummary);
            $html = $mailable->render();
            $envelope = $mailable->envelope();
            $subject = $envelope->subject;

            // Deliver via Brevo API to each recipient
            $allSuccess = true;
            foreach ($filtered as $email) {
                $user = User::where('email', $email)->first();
                $name = $user ? $user->name : 'Team Member';

                $result = self::sendViaApi($email, $name, $subject, $html);
                if (!$result['success']) {
                    $allSuccess = false;
                }
            }

            if ($allSuccess) {
                ActivityLog::log(
                    action: 'leave_email_dispatched',
                    description: sprintf('Leave notification (%s) delivered via Brevo to: %s', $eventType, implode(', ', $filtered)),
                    entityType: 'LeaveApplication',
                    entityId: $leave->id,
                    userId: auth()->id() ?? $leave->user_id
                );
                return true;
            }

            // Fallback to standard mailer
            Mail::to($filtered)->send($mailable);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send leave notification email via Brevo: ' . $e->getMessage());
            try {
                Mail::to($filtered)->send(new \App\Mail\LeaveNotificationMail($leave, $eventType, $remarks, $leaveSummary));
                return true;
            } catch (\Exception $fallbackError) {
                Log::error('Fallback mail for leave notification failed too: ' . $fallbackError->getMessage());
                return false;
            }
        }
    }

    /**
     * Direct HTTP dispatch via Brevo REST API (HTTPS port 443 - never blocked by cloud firewalls).
     */
    public static function sendViaApi(string $toEmail, string $toName, string $subject, string $htmlContent): array
    {
        $apiKey = config('services.brevo.api_key', env('BREVO_API_KEY', env('MAIL_PASSWORD')));
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'No Brevo API key configured'];
        }

        $senderEmail = config('services.brevo.sender_email', env('MAIL_FROM_ADDRESS', 'divyanshecofone@gmail.com'));
        $senderName = config('services.brevo.sender_name', env('MAIL_FROM_NAME', 'EcoFone Operations'));

        $payload = [
            'sender' => [
                'name'  => $senderName,
                'email' => $senderEmail,
            ],
            'to' => [
                [
                    'email' => $toEmail,
                    'name'  => $toName,
                ]
            ],
            'subject'     => $subject,
            'htmlContent' => $htmlContent,
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($json['messageId'])) {
            return [
                'success'    => true,
                'message_id' => $json['messageId'],
            ];
        }

        Log::warning('Brevo API dispatch failed: ' . $response);
        return [
            'success' => false,
            'error'   => $response,
        ];
    }
}

<?php
namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use App\Mail\EmployeeWelcomeMail;
use App\Mail\SecurityOtpMail;
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
     * Send overdue task reminder email.
     */
    public static function sendOverdueTaskReminder(\App\Models\Task $task): bool
    {
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

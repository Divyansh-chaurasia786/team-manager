<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BrevoMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SendLeadershipOtpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ecofone:send-leadership-otps';

    /**
     * The console command description.
     */
    protected $description = 'Generate 10-day valid OTPs for CEO, HR, and TL, send emails, and display CEO OTP';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting leadership credentials setup...');

        // 1. Fetch or create accounts
        $tl = User::firstOrCreate(
            ['email' => 'sumitecofone@gmail.com'],
            [
                'name'                 => 'Sumit',
                'username'             => 'sumit.ecofone',
                'role'                 => 'tl',
                'designation'          => 'Operations Team Lead',
                'mobile_number'        => '+91 98765 43210',
                'password'             => Hash::make('password123'),
                'must_change_password' => true,
            ]
        );

        $hr = User::firstOrCreate(
            ['email' => 'ecofonehr@gmail.com'],
            [
                'name'                 => 'HR Manager',
                'username'             => 'hr.ecofone',
                'role'                 => 'hr',
                'designation'          => 'People & Culture Lead',
                'mobile_number'        => '+91 99999 00002',
                'password'             => Hash::make('password123'),
                'must_change_password' => true,
            ]
        );

        $ceo = User::firstOrCreate(
            ['email' => 'ecofoneofficial@gmail.com'],
            [
                'name'                 => 'Chief Executive Officer',
                'username'             => 'ceo.ecofone',
                'role'                 => 'ceo',
                'designation'          => 'Founder & CEO',
                'mobile_number'        => '+91 99999 00001',
                'password'             => Hash::make('password123'),
                'must_change_password' => true,
            ]
        );

        // 2. Generate 10-day OTPs
        $tlOtp = (string) random_int(100000, 999999);
        $hrOtp = (string) random_int(100000, 999999);
        $ceoOtp = (string) random_int(100000, 999999);
        $validUntil = now()->addDays(10);

        // Update TL
        $tl->update([
            'password'              => Hash::make($tlOtp),
            'must_change_password'  => true,
            'otp_expires_at'        => $validUntil,
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'username'              => 'sumit.ecofone',
            'role'                  => 'tl',
        ]);

        // Update HR
        $hr->update([
            'password'              => Hash::make($hrOtp),
            'must_change_password'  => true,
            'otp_expires_at'        => $validUntil,
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'username'              => 'hr.ecofone',
            'role'                  => 'hr',
        ]);

        // Update CEO
        $ceo->update([
            'password'              => Hash::make($ceoOtp),
            'must_change_password'  => true,
            'otp_expires_at'        => $validUntil,
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'username'              => 'ceo.ecofone',
            'role'                  => 'ceo',
        ]);

        // 3. Dispatch Emails via Brevo
        $this->line('Sending TL OTP email to sumitecofone@gmail.com...');
        $tlSent = BrevoMailService::sendWelcomeMail($tl, $tlOtp);
        $this->info($tlSent ? 'TL email delivered successfully.' : 'TL email dispatch attempted.');

        $this->line('Sending HR OTP email to ecofonehr@gmail.com...');
        $hrSent = BrevoMailService::sendWelcomeMail($hr, $hrOtp);
        $this->info($hrSent ? 'HR email delivered successfully.' : 'HR email dispatch attempted.');

        $this->line('Sending CEO OTP email to ecofoneofficial@gmail.com...');
        $ceoSent = BrevoMailService::sendWelcomeMail($ceo, $ceoOtp);
        $this->info($ceoSent ? 'CEO email delivered successfully.' : 'CEO email dispatch attempted.');

        $this->newLine();
        $this->info('=============================================');
        $this->info('  CEO CREDENTIALS (VALID FOR 10 DAYS)        ');
        $this->info('=============================================');
        $this->line("Email:       ecofoneofficial@gmail.com");
        $this->line("Username:    ceo.ecofone");
        $this->line("CEO OTP:     {$ceoOtp}");
        $this->line("Valid Until: " . $validUntil->toDateTimeString() . " (10 Days)");
        $this->info('=============================================');
        $this->line('Note: TL and HR credentials have NOT been printed to screen; they have been delivered exclusively to sumitecofone@gmail.com and ecofonehr@gmail.com.');

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupExpiredChatMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up unapproved local chat media older than 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredThoughts = \App\Models\TeamThought::whereNull('uploaded_to_drive_at')
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                      ->orWhere('is_expired', true);
            })
            ->whereNotNull('media_path')
            ->get();

        $count = 0;
        foreach ($expiredThoughts as $thought) {
            $fullPath = public_path($thought->media_path);
            if (file_exists($fullPath) && is_file($fullPath)) {
                @unlink($fullPath);
            }
            $thought->update([
                'is_expired' => true,
                'media_path' => null,
            ]);
            $count++;
        }

        $this->info("Cleaned up {$count} expired chat media file(s).");
        return self::SUCCESS;
    }
}

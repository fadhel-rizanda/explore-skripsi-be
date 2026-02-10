<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupPendingUploads extends Command
{
    protected $signature = 'uploads:cleanup'; // php artisan uploads:cleanup, fungsinya biar bisa dieksekusi oleh eksternal (cronjob)

    protected $description = 'Cleanup pending uploads older than 1 hour';

    public function handle()
    {
        $this->info('Starting cleanup for pending uploads...');

        Attachment::where('status', 'pending')
            ->where('created_at', '<', now()->subHour())
            ->chunkById(100, function ($documents) {
                foreach ($documents as $document) {
                    try {
                        if ($document->path && Storage::disk('s3')->exists($document->path)) {
                            Storage::disk('s3')->delete($document->path);
                        }

                        $document->delete();
                        $this->comment("Deleted: {$document->id}");

                    } catch (\Exception $e) {
                        Log::error("Failed to cleanup attachment ID: {$document->id}", [
                            'path' => $document->path,
                            'error' => $e->getMessage(),
                        ]);

                        $document->update(['status' => 'failed']);
                    }
                }
            });

        $this->info('Cleanup process completed.');
    }
}

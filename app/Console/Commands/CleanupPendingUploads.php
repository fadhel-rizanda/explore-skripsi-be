<?php

namespace App\Console\Commands;

use App\Models\AdoptionDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupPendingUploads extends Command
{
    protected $signature = 'uploads:cleanup';
    protected $description = 'Cleanup pending uploads older than 1 hour';

    public function handle()
    {
        $documents = AdoptionDocument::where('status', 'pending')
            ->where('created_at', '<', now()->subHour())
            ->get();

        foreach ($documents as $document) {
            try {
                Storage::disk('s3')->delete($document->path);
            } catch (\Exception $e) {
                Log::error('Failed to delete file from S3: ' . $document->path, ['error' => $e->getMessage()]);
                $document->status = 'failed';
                $document->save();
                continue; // Or break if you want to stop processing further documents
            }
            $document->delete();
        }

        $this->info("Cleaned up {$documents->count()} pending uploads");
    }
}

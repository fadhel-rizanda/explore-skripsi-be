<?php

namespace App\Console\Commands;

use App\Models\AdoptionDocument;
use Illuminate\Console\Command;
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
            if (Storage::disk('s3')->exists($document->path)) {
                Storage::disk('s3')->delete($document->path);
            }
            $document->delete();
        }

        $this->info("Cleaned up {$documents->count()} pending uploads");
    }
}

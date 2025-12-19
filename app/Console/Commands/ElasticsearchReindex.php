<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MediaFile;

class ElasticsearchReindex extends Command
{
    protected $signature = 'elasticsearch:reindex {--force : Force reindex even if no changes}';
    protected $description = 'Reindex all media files into Elasticsearch';

    public function handle()
    {
        $this->info('🔄 Reindexing media files into Elasticsearch...');

        // Get all searchable files
        $files = MediaFile::where('processing_status', 'completed')
            ->whereNotNull('description')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $total = $files->count();

        if ($total === 0) {
            $this->warn('⚠️  No files to index');
            return 0;
        }

        $this->info("📊 Found {$total} files to index");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $indexed = 0;
        $failed = 0;

        foreach ($files as $file) {
            try {
                $file->searchable();
                $indexed++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to index file {$file->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Reindexing complete!");
        $this->info("   Indexed: {$indexed}");
        if ($failed > 0) {
            $this->warn("   Failed: {$failed}");
        }

        return 0;
    }
}

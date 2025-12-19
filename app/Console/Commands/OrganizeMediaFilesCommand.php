<?php

namespace App\Console\Commands;

use App\Services\FolderOrganizationService;
use Illuminate\Console\Command;

class OrganizeMediaFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:organize
                          {--force : Force re-organization of all files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Organize media files into folders based on date and metadata';

    /**
     * Execute the console command.
     */
    public function handle(FolderOrganizationService $service): int
    {
        $this->info('🗂️  Starting media file organization...');
        $this->newLine();

        try {
            // Create media type folders first
            $this->info('📁 Creating default folders...');
            $service->createMediaTypeFolders();
            $this->info('✅ Default folders created');
            $this->newLine();

            // Organize all files
            $this->info('📂 Organizing files by date...');
            $count = $service->organizeAllFiles();
            
            $this->newLine();
            $this->info("✅ Successfully organized {$count} files");
            $this->newLine();

            // Show recent folders
            $this->info('📊 Recent folders:');
            $recentFolders = $service->getRecentFolders(10);
            
            foreach ($recentFolders as $folder) {
                $this->line("  📁 {$folder['path']} - {$folder['file_count']} files ({$folder['total_size']})");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to organize files: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

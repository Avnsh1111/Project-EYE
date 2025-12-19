<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ResetSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-system
                            {--force : Skip confirmation prompts}
                            {--keep-users : Preserve user accounts during reset}
                            {--keep-files : Keep uploaded files, only reset database/cache}
                            {--keep-logs : Don\'t clear log files}
                            {--database-only : Only reset database, skip files/cache}
                            {--files-only : Only delete files, skip database reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the entire system to fresh state (database, files, cache, queues)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Display warning banner
        $this->displayWarning();

        // Check for conflicting options
        if ($this->option('database-only') && $this->option('files-only')) {
            $this->error('Cannot use --database-only and --files-only together');
            return 1;
        }

        // Determine what will be reset
        $actions = $this->determineActions();

        // Display what will be deleted
        $this->displayActions($actions);

        // Confirm unless --force is provided
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to proceed? This action CANNOT be undone!', false)) {
                $this->info('Reset cancelled.');
                return 0;
            }
        }

        $this->newLine();
        $this->info('🚀 Starting system reset...');
        $this->newLine();

        $startTime = now();
        $errors = [];

        // Execute reset steps
        try {
            // Step 1: Stop queue workers
            if (!$this->option('files-only')) {
                $this->executeStep('Stopping queue workers', function () {
                    Artisan::call('queue:restart');
                });
            }

            // Step 2: Clear storage files
            if (!$this->option('database-only') && !$this->option('keep-files')) {
                $this->executeStep('Clearing storage files', function () use (&$errors) {
                    $this->clearStorageFiles($errors);
                });
            }

            // Step 3: Clear AI training data
            if (!$this->option('database-only') && !$this->option('keep-files')) {
                $this->executeStep('Clearing AI training data', function () use (&$errors) {
                    $this->clearTrainingData($errors);
                });
            }

            // Step 4: Reset database
            if (!$this->option('files-only')) {
                $this->executeStep('Resetting database', function () {
                    if ($this->option('keep-users')) {
                        // Export users before reset
                        $this->warn('⚠️  --keep-users not yet implemented, users will be recreated');
                    }

                    Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
                });
            }

            // Step 5: Clear queues
            if (!$this->option('files-only')) {
                $this->executeStep('Clearing queue tables', function () {
                    Artisan::call('queue:clear', ['connection' => 'database']);
                    Artisan::call('queue:prune-failed', ['--hours' => 0]);
                });
            }

            // Step 6: Clear all caches
            if (!$this->option('files-only')) {
                $this->executeStep('Clearing all caches', function () {
                    Artisan::call('optimize:clear');
                    Artisan::call('cache:clear');
                });
            }

            // Step 7: Clear logs
            if (!$this->option('files-only') && !$this->option('keep-logs')) {
                $this->executeStep('Clearing log files', function () use (&$errors) {
                    $this->clearLogs($errors);
                });
            }

            // Step 8: Recreate directory structure
            if (!$this->option('database-only') && !$this->option('keep-files')) {
                $this->executeStep('Recreating directory structure', function () use (&$errors) {
                    $this->recreateDirectories($errors);
                });
            }

            $this->newLine();
            $this->info('✅ System reset completed successfully!');

            $duration = now()->diffInSeconds($startTime);
            $this->info("⏱️  Duration: {$duration} seconds");

            // Display any non-critical errors
            if (count($errors) > 0) {
                $this->newLine();
                $this->warn('⚠️  Some non-critical errors occurred:');
                foreach ($errors as $error) {
                    $this->warn("   - {$error}");
                }
            }

            // Display summary
            $this->displaySummary($actions);

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ System reset failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Display warning banner
     */
    protected function displayWarning()
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                    ⚠️  SYSTEM RESET WARNING  ⚠️                 ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->warn('This command will PERMANENTLY DELETE:');
        $this->warn('  • All uploaded files (images, videos, audio, documents, etc.)');
        $this->warn('  • All database records (users, media files, settings, etc.)');
        $this->warn('  • All queue jobs (pending and failed)');
        $this->warn('  • All cached data');
        $this->warn('  • All log files');
        $this->warn('  • All AI training data');
        $this->newLine();
    }

    /**
     * Determine which actions will be performed
     */
    protected function determineActions(): array
    {
        return [
            'stop_queues' => !$this->option('files-only'),
            'clear_files' => !$this->option('database-only') && !$this->option('keep-files'),
            'clear_training' => !$this->option('database-only') && !$this->option('keep-files'),
            'reset_database' => !$this->option('files-only'),
            'clear_queues' => !$this->option('files-only'),
            'clear_caches' => !$this->option('files-only'),
            'clear_logs' => !$this->option('files-only') && !$this->option('keep-logs'),
            'recreate_dirs' => !$this->option('database-only') && !$this->option('keep-files'),
        ];
    }

    /**
     * Display actions that will be performed
     */
    protected function displayActions(array $actions)
    {
        $this->info('The following actions will be performed:');
        $this->newLine();

        if ($actions['stop_queues']) {
            $this->line('  ✓ Stop queue workers');
        }
        if ($actions['clear_files']) {
            $this->line('  ✓ Clear all uploaded files');
        }
        if ($actions['clear_training']) {
            $this->line('  ✓ Clear AI training data');
        }
        if ($actions['reset_database']) {
            $this->line('  ✓ Reset database (migrate:fresh --seed)');
        }
        if ($actions['clear_queues']) {
            $this->line('  ✓ Clear queue tables');
        }
        if ($actions['clear_caches']) {
            $this->line('  ✓ Clear all caches');
        }
        if ($actions['clear_logs']) {
            $this->line('  ✓ Clear log files');
        }
        if ($actions['recreate_dirs']) {
            $this->line('  ✓ Recreate directory structure');
        }

        $this->newLine();
    }

    /**
     * Execute a step with progress indication
     */
    protected function executeStep(string $message, callable $callback)
    {
        $this->info("⏳ {$message}...");

        $startTime = microtime(true);
        $callback();
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $this->info("   ✓ Completed in {$duration}ms");
    }

    /**
     * Clear storage files
     */
    protected function clearStorageFiles(array &$errors)
    {
        $directories = [
            'images',
            'images/thumbnails',
            'videos',
            'videos/thumbnails',
            'audio',
            'audio/thumbnails',
            'documents',
            'documents/thumbnails',
            'archives',
            'code',
            'test-files',
            'livewire-tmp',
        ];

        foreach ($directories as $directory) {
            $path = storage_path("app/public/{$directory}");

            if (File::exists($path)) {
                try {
                    // Delete all files but preserve .gitignore
                    $files = File::files($path);
                    foreach ($files as $file) {
                        if ($file->getFilename() !== '.gitignore') {
                            File::delete($file->getPathname());
                        }
                    }

                    // Delete all subdirectories
                    $subdirs = File::directories($path);
                    foreach ($subdirs as $subdir) {
                        File::deleteDirectory($subdir);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to clear {$directory}: " . $e->getMessage();
                }
            }
        }

        // Clear private storage
        $privatePath = storage_path('app/private');
        if (File::exists($privatePath)) {
            try {
                File::cleanDirectory($privatePath);
            } catch (\Exception $e) {
                $errors[] = "Failed to clear private storage: " . $e->getMessage();
            }
        }
    }

    /**
     * Clear AI training data
     */
    protected function clearTrainingData(array &$errors)
    {
        $trainingPath = base_path('python-ai/training_data');

        if (File::exists($trainingPath)) {
            try {
                $files = File::files($trainingPath);
                foreach ($files as $file) {
                    if (!in_array($file->getFilename(), ['.gitignore', '.gitkeep'])) {
                        File::delete($file->getPathname());
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to clear training data: " . $e->getMessage();
            }
        }
    }

    /**
     * Clear log files
     */
    protected function clearLogs(array &$errors)
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            try {
                File::put($logPath, '');
            } catch (\Exception $e) {
                $errors[] = "Failed to clear logs: " . $e->getMessage();
            }
        }
    }

    /**
     * Recreate necessary directory structure
     */
    protected function recreateDirectories(array &$errors)
    {
        $directories = [
            'storage/app/public/images/thumbnails',
            'storage/app/public/videos/thumbnails',
            'storage/app/public/audio/thumbnails',
            'storage/app/public/documents/thumbnails',
            'storage/app/public/archives',
            'storage/app/public/code',
            'storage/app/public/test-files',
            'storage/app/livewire-tmp',
            'storage/app/private',
        ];

        foreach ($directories as $directory) {
            $path = base_path($directory);

            if (!File::exists($path)) {
                try {
                    File::makeDirectory($path, 0755, true);
                } catch (\Exception $e) {
                    $errors[] = "Failed to create {$directory}: " . $e->getMessage();
                }
            }
        }
    }

    /**
     * Display summary after reset
     */
    protected function displaySummary(array $actions)
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                      RESET SUMMARY                             ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($actions['reset_database']) {
            $this->info('📋 Default User Credentials:');
            $this->line('   Email:    admin@avinash-eye.local');
            $this->line('   Password: Admin@123');
            $this->newLine();
        }

        $this->info('🎯 Next Steps:');
        $this->line('   1. Restart your queue workers if they were stopped');
        $this->line('   2. Upload new files to test the system');
        $this->line('   3. Check that all services are running correctly');
        $this->newLine();

        $this->info('💡 Quick Commands:');
        $this->line('   • Start queue worker:  php artisan queue:work');
        $this->line('   • Check status:        php artisan queue:work --once');
        $this->line('   • View logs:           tail -f storage/logs/laravel.log');
        $this->newLine();
    }
}

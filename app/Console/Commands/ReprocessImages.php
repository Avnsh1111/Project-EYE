<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImageFile;
use App\Models\Setting;
use App\Jobs\ProcessImageAnalysis;
use App\Services\SystemMonitorService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReprocessImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:reprocess 
                            {--batch=10 : Number of images to process in this run}
                            {--force : Force reprocess all images}
                            {--only-missing : Only reprocess images missing certain features}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess images to improve descriptions, categorization, and face detection when system is idle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Record scheduler activity for monitoring
        SystemMonitorService::recordSchedulerActivity();
        
        $this->info('🔄 Starting intelligent image reprocessing...');
        
        $batch = (int) $this->option('batch');
        $force = $this->option('force');
        $onlyMissing = $this->option('only-missing');
        
        // Get current AI settings
        $faceDetectionEnabled = Setting::get('face_detection_enabled', true);
        $ollamaEnabled = Setting::get('ollama_enabled', false);
        
        // Build query to find images that need reprocessing
        $query = ImageFile::where('processing_status', 'completed')
            ->whereNull('deleted_at');
        
        if ($force) {
            $this->warn('⚠️  Force mode: Reprocessing ALL images');
        } elseif ($onlyMissing) {
            $this->info('🔍 Only reprocessing images with missing features');
            $query->where(function ($q) use ($faceDetectionEnabled, $ollamaEnabled) {
                // Missing face detection
                if ($faceDetectionEnabled) {
                    $q->orWhereNull('face_count')
                      ->orWhereNull('face_encodings');
                }
                
                // Missing detailed description from Ollama
                if ($ollamaEnabled) {
                    $q->orWhereNull('detailed_description')
                      ->orWhere('detailed_description', '');
                }
                
                // Missing meta tags
                $q->orWhereNull('meta_tags')
                  ->orWhereJsonLength('meta_tags', 0);
            });
        } else {
            // Smart reprocessing: prioritize images that could benefit most
            $this->info('🧠 Smart mode: Prioritizing images that need improvement');
            
            // Prioritize:
            // 1. Images without face detection (if enabled)
            // 2. Images without detailed descriptions (if Ollama enabled)
            // 3. Images without meta tags
            // 4. Older images that were processed before features were available
            $query->where(function ($q) use ($faceDetectionEnabled, $ollamaEnabled) {
                if ($faceDetectionEnabled) {
                    $q->orWhereNull('face_count');
                }
                
                if ($ollamaEnabled) {
                    $q->orWhereNull('detailed_description')
                      ->orWhere('detailed_description', '');
                }
                
                $q->orWhereNull('meta_tags')
                  ->orWhereJsonLength('meta_tags', 0)
                  // Or images processed more than 7 days ago
                  ->orWhere('updated_at', '<', Carbon::now()->subDays(7));
            });
        }
        
        // Order by priority: newest first (more likely to be viewed)
        $images = $query->orderBy('created_at', 'desc')
            ->limit($batch)
            ->get();
        
        if ($images->isEmpty()) {
            $this->info('✅ No images need reprocessing!');
            return Command::SUCCESS;
        }
        
        $this->info("📸 Found {$images->count()} images to reprocess");
        
        $progressBar = $this->output->createProgressBar($images->count());
        $progressBar->start();
        
        $processed = 0;
        $failed = 0;
        
        foreach ($images as $image) {
            try {
                // Dispatch job to reprocess the image
                ProcessImageAnalysis::dispatch($image->id);
                
                $processed++;
                
                Log::info('Reprocessing image', [
                    'image_id' => $image->id,
                    'filename' => $image->original_filename,
                    'reason' => $this->getReprocessReason($image, $faceDetectionEnabled, $ollamaEnabled)
                ]);
                
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to dispatch reprocess job', [
                    'image_id' => $image->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $progressBar->advance();
            
            // Small delay to avoid overwhelming the system
            usleep(100000); // 100ms
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✅ Dispatched {$processed} reprocessing jobs");
        
        if ($failed > 0) {
            $this->warn("⚠️  Failed to dispatch {$failed} jobs");
        }
        
        $this->info('💡 Jobs will be processed by the queue worker');
        $this->comment('   Run: php artisan queue:work');
        
        return Command::SUCCESS;
    }
    
    /**
     * Get the reason why an image is being reprocessed.
     *
     * @param ImageFile $image
     * @param bool $faceDetectionEnabled
     * @param bool $ollamaEnabled
     * @return string
     */
    protected function getReprocessReason(ImageFile $image, bool $faceDetectionEnabled, bool $ollamaEnabled): string
    {
        $reasons = [];
        
        if ($faceDetectionEnabled && (!$image->face_count || !$image->face_encodings)) {
            $reasons[] = 'missing face detection';
        }
        
        if ($ollamaEnabled && (!$image->detailed_description || empty($image->detailed_description))) {
            $reasons[] = 'missing detailed description';
        }
        
        if (!$image->meta_tags || empty($image->meta_tags)) {
            $reasons[] = 'missing meta tags';
        }
        
        if (empty($reasons) && $image->updated_at < Carbon::now()->subDays(7)) {
            $reasons[] = 'old processing (7+ days)';
        }
        
        return implode(', ', $reasons) ?: 'general improvement';
    }
}

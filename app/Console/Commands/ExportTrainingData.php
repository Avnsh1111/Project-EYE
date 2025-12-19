<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImageFile;

class ExportTrainingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:training-data 
                            {--limit=1000 : Maximum number of images to export}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export image metadata for AI model training';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Exporting training data...');
        
        $limit = (int) $this->option('limit');
        
        // Get images with complete metadata (use PostgreSQL connection)
        $images = ImageFile::on('pgsql')
            ->where('processing_status', 'completed')
            ->whereNull('deleted_at')
            ->whereNotNull('embedding')
            ->limit($limit)
            ->get();
        
        if ($images->isEmpty()) {
            $this->warn('No images found for training');
            return Command::FAILURE;
        }
        
        $this->info("Found {$images->count()} images");
        
        // Prepare training data
        $trainingData = [];
        $progressBar = $this->output->createProgressBar($images->count());
        $progressBar->start();
        
        foreach ($images as $image) {
            $trainingData[] = [
                'filename' => basename($image->file_path),
                'description' => $image->description,
                'detailed_description' => $image->detailed_description,
                'meta_tags' => $image->meta_tags ?? [],
                'face_count' => $image->face_count ?? 0,
                'embedding' => $image->embedding,
                'created_at' => $image->created_at->toIso8601String(),
            ];
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Save to Docker volume (accessible by Python AI container)
        // This path is mounted as a volume in docker-compose.yml
        $outputPath = base_path('python-ai/training_data/images_metadata.json');
        $outputDir = dirname($outputPath);
        
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        file_put_contents($outputPath, json_encode($trainingData, JSON_PRETTY_PRINT));
        
        // Also save a timestamp
        file_put_contents(
            dirname($outputPath) . '/export_timestamp.txt',
            date('Y-m-d H:i:s') . " - Exported {$images->count()} images\n",
            FILE_APPEND
        );
        
        $this->info("✅ Exported {$images->count()} images to training data");
        $this->info("📁 File: {$outputPath}");
        $this->newLine();
        $this->info("🚀 Next steps:");
        $this->comment("   1. Run training: docker compose exec python-ai python train_model.py");
        $this->comment("   2. Rebuild container: docker compose restart python-ai");
        $this->comment("   3. New images will use learned patterns automatically!");
        
        return Command::SUCCESS;
    }
}

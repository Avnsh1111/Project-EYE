<?php

namespace App\Jobs;

use App\Models\MediaFile;
use App\Models\Setting;
use App\Services\AiService;
use App\Services\MetadataService;
use App\Services\FileService;
use App\Services\SystemMonitorService;
use App\Services\FaceClusteringService;
use App\Repositories\ImageRepository;
use App\Services\Processors\VideoProcessor;
use App\Services\Processors\DocumentProcessor;
use App\Services\Processors\AudioProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProcessImageAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Retry 3 times on failure

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $imageFileId
    ) {}

    /**
     * Find media file by ID, instantiating the correct STI class.
     *
     * @param int $id
     * @return MediaFile|null
     */
    private function findMediaFile(int $id): ?MediaFile
    {
        // Use MediaFile::find() which automatically handles STI
        // It will return the correct subclass if one exists, or base MediaFile otherwise
        return MediaFile::find($id);
    }

    /**
     * Execute the job.
     */
    public function handle(
        AiService $aiService,
        MetadataService $metadataService,
        FileService $fileService,
        ImageRepository $imageRepository,
        VideoProcessor $videoProcessor,
        DocumentProcessor $documentProcessor,
        AudioProcessor $audioProcessor
    ): void {
        // Record queue worker activity for monitoring
        SystemMonitorService::recordQueueActivity();

        // Find media file using STI-aware helper (works for all media types)
        $imageFile = $this->findMediaFile($this->imageFileId);

        if (!$imageFile) {
            Log::warning("Media file not found: {$this->imageFileId}");
            return;
        }

        try {
            Log::info("Starting deep analysis via services for image: {$imageFile->id}");

            // Update status to processing
            $imageFile->update([
                'processing_status' => 'processing',
                'processing_started_at' => now(),
                'upload_completed_at' => $imageFile->upload_completed_at ?? now(),
                'upload_progress' => 100,
                'processing_stage' => 'starting',
            ]);

            // Extract comprehensive metadata using MetadataService
            $fullPath = $fileService->getFullPath($imageFile->file_path);
            $metadata = $metadataService->extractComprehensiveMetadata($fullPath);

            // Update progress: metadata extracted
            $imageFile->update(['processing_stage' => 'metadata_extracted']);

            // Get media_type from the record (fallback to MIME type detection)
            $mediaType = $imageFile->media_type ?? $this->detectMediaType($imageFile->mime_type ?? mime_content_type($fullPath));

            Log::info("Processing media file type: {$mediaType}", [
                'file_id' => $imageFile->id,
                'filename' => $imageFile->original_filename,
            ]);

            // Update progress: starting AI analysis
            $imageFile->update(['processing_stage' => 'analyzing']);

            // Route to appropriate processor based on media_type
            $analysis = match($mediaType) {
                'image' => $this->processImage($aiService, $imageFile, $fullPath),
                'video' => $this->processVideo($aiService, $videoProcessor, $imageFile, $fullPath),
                'document' => $this->processDocument($aiService, $documentProcessor, $imageFile, $fullPath),
                'audio' => $this->processAudio($aiService, $audioProcessor, $imageFile, $fullPath),
                'email' => $this->processEmail($imageFile, $fullPath),
                'archive' => $this->processArchive($imageFile, $fullPath),
                'code' => $this->processCode($imageFile, $fullPath),
                default => $this->processUnknown($imageFile, $fullPath),
            };

            // Update progress: analysis complete
            $imageFile->update(['processing_stage' => 'saving_results']);

            // Merge metadata and analysis results, then update
            $updateData = array_merge($metadata, [
                'description' => $this->sanitizeForPostgres($analysis['description']),
                'detailed_description' => isset($analysis['detailed_description']) ? $this->sanitizeForPostgres($analysis['detailed_description']) : null,
                'meta_tags' => $analysis['meta_tags'] ?? [],
                'embedding' => $analysis['embedding'] ?? null,
                'face_count' => $analysis['face_count'] ?? 0,
                'face_encodings' => $analysis['face_encodings'] ?? [],
                'thumbnail_path' => $analysis['thumbnail_path'] ?? null,
                // Document-specific fields
                'extracted_text' => isset($analysis['extracted_text']) ? $this->sanitizeForPostgres($analysis['extracted_text']) : null,
                'document_type' => $analysis['document_type'] ?? null,
                'classification_confidence' => $analysis['classification_confidence'] ?? null,
                'entities' => $analysis['entities'] ?? null,
                // Maximum analysis coverage fields (image-specific)
                'objects_detected' => $analysis['objects_detected'] ?? null,
                'scene_classification' => $analysis['scene_classification'] ?? null,
                'dominant_colors' => $analysis['dominant_colors'] ?? null,
                'image_quality' => $analysis['image_quality'] ?? null,
                'quality_tier' => $analysis['quality_tier'] ?? null,
                'phash' => $analysis['phash'] ?? null,
                'dhash' => $analysis['dhash'] ?? null,
                'processing_status' => 'completed',
                'processing_completed_at' => now(),
                'processing_error' => null,
            ]);

            $imageFile->update($updateData);

            // Process and cluster detected faces (only for images)
            if ($mediaType === 'image' && !empty($analysis['faces'])) {
                try {
                    $faceClusteringService = app(FaceClusteringService::class);
                    $faceClusteringService->processFaces($imageFile, $analysis['faces']);
                    Log::info("Clustered {$analysis['face_count']} face(s) for image: {$imageFile->id}");
                } catch (\Exception $e) {
                    Log::error("Face clustering failed for image {$imageFile->id}: {$e->getMessage()}");
                }
            }

            Log::info("Deep analysis completed via services for image: {$imageFile->id}");

            // Dispatch event for real-time updates
            $imageFile->refresh(); // Reload from database
            event(new \App\Events\ImageProcessed($imageFile));

        } catch (\Exception $e) {
            Log::error("Failed to analyze image via services {$imageFile->id}: {$e->getMessage()}");

            $imageFile->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage(),
                'processing_completed_at' => now(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $imageFile = $this->findMediaFile($this->imageFileId);

        if ($imageFile) {
            $imageFile->update([
                'processing_status' => 'failed',
                'processing_error' => $exception->getMessage(),
                'processing_completed_at' => now(),
            ]);
        }

        Log::error("Image analysis job failed permanently for image {$this->imageFileId}: {$exception->getMessage()}");
    }

    /**
     * Detect media type from MIME type.
     */
    private function detectMediaType(string $mimeType): string
    {
        return match(true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            // PDF and text documents
            in_array($mimeType, ['application/pdf', 'text/plain', 'text/csv']) => 'document',
            // Word documents
            in_array($mimeType, [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                'application/msword', // .doc
                'application/vnd.oasis.opendocument.text', // .odt
                'application/rtf', // .rtf
                'text/rtf'
            ]) => 'document',
            // Spreadsheets
            in_array($mimeType, [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                'application/vnd.ms-excel', // .xls
                'application/vnd.oasis.opendocument.spreadsheet', // .ods
                'text/csv'
            ]) => 'document',
            // Presentations
            in_array($mimeType, [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx
                'application/vnd.ms-powerpoint', // .ppt
                'application/vnd.oasis.opendocument.presentation' // .odp
            ]) => 'document',
            // Email files
            in_array($mimeType, [
                'message/rfc822', // .eml
                'application/vnd.ms-outlook', // .msg
                'application/vnd.ms-outlook-pst' // .pst
            ]) => 'email',
            // Archives
            in_array($mimeType, [
                'application/zip',
                'application/x-rar',
                'application/x-rar-compressed',
                'application/x-tar',
                'application/x-7z-compressed',
                'application/gzip',
                'application/x-gzip'
            ]) => 'archive',
            // Code files (common programming languages)
            in_array($mimeType, [
                'text/x-python',
                'text/x-php',
                'text/x-java-source',
                'text/x-c',
                'text/x-c++',
                'text/x-ruby',
                'text/x-go',
                'text/x-rust',
                'text/javascript',
                'application/javascript',
                'application/json',
                'application/xml',
                'text/xml',
                'text/html',
                'text/css',
                'application/x-yaml',
                'text/x-yaml'
            ]) => 'code',
            default => 'unknown',
        };
    }

    /**
     * Process an image file with AI analysis.
     */
    private function processImage(AiService $aiService, MediaFile $imageFile, string $fullPath): array
    {
        Log::info("Running AI image analysis for: {$imageFile->original_filename}");
        return $aiService->analyzeImage($imageFile->file_path);
    }

    /**
     * Process a video file with AI analysis.
     */
    private function processVideo(AiService $aiService, VideoProcessor $videoProcessor, MediaFile $videoFile, string $fullPath): array
    {
        Log::info("Running AI video analysis for: {$videoFile->original_filename}");

        try {
            // Get AI analysis (scene descriptions, embeddings)
            $aiAnalysis = $aiService->analyzeVideo($videoFile->file_path);

            // Combine scene descriptions into a single description
            $sceneTexts = array_map(fn($scene) => $scene['description'], $aiAnalysis['scene_descriptions'] ?? []);

            // Try to generate coherent description using Ollama
            $description = null;
            $ollamaEnabledRaw = Setting::get('ollama_enabled', false);
            $ollamaEnabled = is_bool($ollamaEnabledRaw) ? $ollamaEnabledRaw : ($ollamaEnabledRaw === 'true' || $ollamaEnabledRaw === true);

            if ($ollamaEnabled && !empty($sceneTexts)) {
                try {
                    $sceneContext = implode("\n- ", array_slice($sceneTexts, 0, 10));
                    $duration = $aiAnalysis['duration_seconds'] ?? 0;
                    $prompt = "Based on these sequential video frame descriptions from a {$duration} second video, write a coherent 2-3 sentence summary describing what happens in the video:\n\n- {$sceneContext}\n\nSummary:";

                    $ollamaModel = Setting::get('ollama_model', 'llava');
                    $response = Http::timeout(90)->post(config('services.ollama.url', 'http://ollama:11434') . '/api/generate', [
                        'model' => $ollamaModel,
                        'prompt' => $prompt,
                        'stream' => false,
                    ]);

                    if ($response->successful()) {
                        $description = trim($response->json()['response'] ?? '');
                        Log::info("Generated Ollama video summary: {$description}");
                    }
                } catch (\Exception $e) {
                    Log::warning("Ollama video summary failed, using fallback: {$e->getMessage()}");
                }
            }

            // Fallback to improved concatenation if Ollama unavailable
            if (empty($description)) {
                $description = !empty($sceneTexts)
                    ? "Video showing: " . implode(", then ", array_slice($sceneTexts, 0, 3))
                    : "Video file: {$videoFile->original_filename}";
            }

            return [
                'description' => $description,
                'detailed_description' => implode(". ", $sceneTexts),
                'meta_tags' => ['video', pathinfo($videoFile->original_filename, PATHINFO_EXTENSION)],
                'embedding' => $aiAnalysis['embedding'] ?? null,
                'face_count' => 0,
                'face_encodings' => [],
                'faces' => [],
            ];
        } catch (\Exception $e) {
            Log::warning("AI video analysis failed, using basic metadata: {$e->getMessage()}");
            return $this->processUnknown($videoFile, $fullPath);
        }
    }

    /**
     * Process a document file with OCR and intelligent analysis.
     */
    private function processDocument(AiService $aiService, DocumentProcessor $documentProcessor, MediaFile $documentFile, string $fullPath): array
    {
        Log::info("Running AI document analysis (OCR + Intelligence) for: {$documentFile->original_filename}");

        try {
            $aiAnalysis = $aiService->analyzeDocument($documentFile->file_path);

            $extractedText = $aiAnalysis['extracted_text'] ?? '';

            // Use intelligent summary if available, otherwise fallback to truncated text
            $intelligentSummary = $aiAnalysis['summary'] ?? null;
            $description = !empty($intelligentSummary)
                ? $intelligentSummary
                : (!empty($extractedText) ? "Document: " . substr($extractedText, 0, 200) : "Document: {$documentFile->original_filename}");

            // Use detailed description from extracted text
            $detailedDescription = !empty($extractedText) ? $extractedText : null;

            // Get document intelligence fields
            $documentType = $aiAnalysis['document_type'] ?? null;
            $classificationConfidence = $aiAnalysis['classification_confidence'] ?? null;
            $entities = $aiAnalysis['entities'] ?? null;

            // Merge keywords from entities and OCR keywords
            $keywords = array_merge(['document'], $aiAnalysis['keywords'] ?? []);
            if ($documentType) {
                $keywords[] = $documentType;
            }

            return [
                'description' => $description,
                'detailed_description' => $detailedDescription,
                'meta_tags' => array_unique($keywords),
                'embedding' => $aiAnalysis['embedding'] ?? null,
                'thumbnail_path' => $aiAnalysis['thumbnail_path'] ?? null,
                'extracted_text' => $extractedText,
                'document_type' => $documentType,
                'classification_confidence' => $classificationConfidence,
                'entities' => $entities,
                'face_count' => 0,
                'face_encodings' => [],
                'faces' => [],
            ];
        } catch (\Exception $e) {
            Log::warning("AI document analysis failed, using basic metadata: {$e->getMessage()}");
            return $this->processUnknown($documentFile, $fullPath);
        }
    }

    /**
     * Process an audio file with transcription.
     */
    private function processAudio(AiService $aiService, AudioProcessor $audioProcessor, MediaFile $audioFile, string $fullPath): array
    {
        Log::info("Running AI audio transcription for: {$audioFile->original_filename}");

        try {
            $aiAnalysis = $aiService->transcribeAudio($audioFile->file_path);

            $transcribedText = $aiAnalysis['text'] ?? '';
            $detectedLanguage = $aiAnalysis['language'] ?? 'unknown';

            $description = !empty($transcribedText)
                ? "Audio: " . substr($transcribedText, 0, 200)
                : "Audio file: {$audioFile->original_filename}";

            Log::info('Audio transcription extracted', [
                'audio_id' => $audioFile->id,
                'text_length' => strlen($transcribedText),
                'language' => $detectedLanguage,
            ]);

            return [
                'description' => $description,
                'detailed_description' => $transcribedText,
                'extracted_text' => $transcribedText,  // Store in extracted_text for consistency with documents
                'meta_tags' => ['audio', $detectedLanguage, pathinfo($audioFile->original_filename, PATHINFO_EXTENSION)],
                'embedding' => $aiAnalysis['embedding'] ?? null,
                'thumbnail_path' => $aiAnalysis['thumbnail_path'] ?? null,
                'face_count' => 0,
                'face_encodings' => [],
                'faces' => [],
            ];
        } catch (\Exception $e) {
            Log::warning("AI audio transcription failed, using basic metadata: {$e->getMessage()}");
            return $this->processUnknown($audioFile, $fullPath);
        }
    }

    /**
     * Process an email file (.eml, .msg).
     */
    private function processEmail(MediaFile $emailFile, string $fullPath): array
    {
        Log::info("Processing email file: {$emailFile->original_filename}");

        try {
            // Call Python API to extract email metadata
            $pythonUrl = config('services.python_ai.url', 'http://python-ai:8000');
            $response = Http::timeout(30)->post("{$pythonUrl}/extract-email", [
                'file_path' => $emailFile->file_path,
            ]);

            if ($response->successful()) {
                $emailData = $response->json();

                // Build intelligent description
                $sender = $emailData['sender'] ?? 'Unknown sender';
                $subject = $emailData['subject'] ?? 'No subject';
                $recipientCount = count($emailData['recipients'] ?? []);
                $attachmentCount = $emailData['attachment_count'] ?? 0;
                $date = $emailData['date'] ?? 'Unknown date';

                $description = "Email from {$sender}: {$subject}";
                if ($attachmentCount > 0) {
                    $description .= " ({$attachmentCount} attachment" . ($attachmentCount > 1 ? 's' : '') . ")";
                }

                // Build detailed description
                $recipients = implode(', ', array_slice($emailData['recipients'] ?? [], 0, 5));
                $body = substr($emailData['body'] ?? '', 0, 500);
                $detailedDescription = "From: {$sender}\nTo: {$recipients}\nDate: {$date}\nSubject: {$subject}\n\n{$body}";

                return [
                    'description' => $description,
                    'detailed_description' => $detailedDescription,
                    'meta_tags' => ['email', pathinfo($emailFile->original_filename, PATHINFO_EXTENSION)],
                    'embedding' => null,
                    'face_count' => 0,
                    'face_encodings' => [],
                    'faces' => [],
                ];
            }

            // Fallback to basic metadata
            return $this->processUnknown($emailFile, $fullPath);

        } catch (\Exception $e) {
            Log::warning("Email processing failed, using basic metadata: {$e->getMessage()}");
            return $this->processUnknown($emailFile, $fullPath);
        }
    }

    /**
     * Process an archive file with intelligent analysis.
     */
    private function processArchive(MediaFile $archiveFile, string $fullPath): array
    {
        Log::info("Processing archive file: {$archiveFile->original_filename}");

        try {
            // Call Python API to extract archive metadata
            $pythonUrl = config('services.python_ai.url', 'http://python-ai:8000');
            $response = Http::timeout(30)->post("{$pythonUrl}/extract-archive-metadata", [
                'file_path' => $archiveFile->file_path,
            ]);

            if ($response->successful()) {
                $archiveData = $response->json();

                $fileCount = $archiveData['file_count'] ?? 0;
                $totalSize = $archiveData['total_size'] ?? 0;
                $fileTypes = $archiveData['file_types'] ?? [];

                // Format size
                $sizeFormatted = $this->formatBytes($totalSize);

                // Build file type summary
                $typesSummary = [];
                arsort($fileTypes);
                foreach (array_slice($fileTypes, 0, 5) as $ext => $count) {
                    $typesSummary[] = "{$count} {$ext}";
                }
                $typesStr = !empty($typesSummary) ? implode(', ', $typesSummary) : 'various files';

                $description = "Archive containing {$fileCount} file" . ($fileCount != 1 ? 's' : '') . " ({$typesStr}), total size: {$sizeFormatted}";

                // Build detailed description with file list
                $fileList = $archiveData['file_list'] ?? [];
                $detailedDescription = "Archive contents:\n";
                foreach (array_slice($fileList, 0, 20) as $file) {
                    $fileName = $file['name'] ?? 'unknown';
                    $fileSize = $this->formatBytes($file['size'] ?? 0);
                    $detailedDescription .= "- {$fileName} ({$fileSize})\n";
                }
                if (count($fileList) > 20) {
                    $detailedDescription .= "... and " . (count($fileList) - 20) . " more files\n";
                }

                return [
                    'description' => $description,
                    'detailed_description' => $detailedDescription,
                    'meta_tags' => array_merge(['archive'], array_keys(array_slice($fileTypes, 0, 5))),
                    'embedding' => null,
                    'face_count' => 0,
                    'face_encodings' => [],
                    'faces' => [],
                ];
            }

            // Fallback to basic metadata
            return [
                'description' => "Archive: {$archiveFile->original_filename}",
                'detailed_description' => "Archive file containing compressed data",
                'meta_tags' => ['archive', pathinfo($archiveFile->original_filename, PATHINFO_EXTENSION)],
                'embedding' => null,
                'face_count' => 0,
                'face_encodings' => [],
                'faces' => [],
            ];

        } catch (\Exception $e) {
            Log::warning("Archive processing failed, using basic metadata: {$e->getMessage()}");
            return [
                'description' => "Archive: {$archiveFile->original_filename}",
                'detailed_description' => "Archive file containing compressed data",
                'meta_tags' => ['archive', pathinfo($archiveFile->original_filename, PATHINFO_EXTENSION)],
                'embedding' => null,
                'face_count' => 0,
                'face_encodings' => [],
                'faces' => [],
            ];
        }
    }

    /**
     * Process a code file with analysis.
     */
    private function processCode(MediaFile $codeFile, string $fullPath): array
    {
        Log::info("Processing code file: {$codeFile->original_filename}");

        try {
            // Call Python API to analyze code file
            $pythonUrl = config('services.python_ai.url', 'http://python-ai:8000');
            $response = Http::timeout(30)->post("{$pythonUrl}/analyze-code-file", [
                'file_path' => $codeFile->file_path,
            ]);

            if ($response->successful()) {
                $codeData = $response->json();

                $language = $codeData['language'] ?? 'Unknown';
                $lineCount = $codeData['line_count'] ?? 0;
                $codeLines = $codeData['code_lines'] ?? 0;
                $commentLines = $codeData['comment_lines'] ?? 0;
                $blankLines = $codeData['blank_lines'] ?? 0;
                $fileSize = $this->formatBytes($codeData['file_size'] ?? 0);
                $extractedText = $codeData['extracted_text'] ?? '';

                $description = "{$language} file: {$lineCount} lines ({$codeLines} code, {$commentLines} comments), {$fileSize}";

                $detailedDescription = "Language: {$language}\n";
                $detailedDescription .= "Total lines: {$lineCount}\n";
                $detailedDescription .= "Code lines: {$codeLines}\n";
                $detailedDescription .= "Comment lines: {$commentLines}\n";
                $detailedDescription .= "Blank lines: {$blankLines}\n";
                $detailedDescription .= "File size: {$fileSize}";

                return [
                    'description' => $description,
                    'detailed_description' => $detailedDescription,
                    'meta_tags' => ['code', strtolower($language), pathinfo($codeFile->original_filename, PATHINFO_EXTENSION)],
                    'extracted_text' => $extractedText,
                    'embedding' => null,
                    'face_count' => 0,
                    'face_encodings' => [],
                    'faces' => [],
                ];
            }

            // Fallback to basic metadata
            return $this->processUnknown($codeFile, $fullPath);

        } catch (\Exception $e) {
            Log::warning("Code file processing failed, using basic metadata: {$e->getMessage()}");
            return $this->processUnknown($codeFile, $fullPath);
        }
    }

    /**
     * Process an unknown/unsupported file type.
     */
    private function processUnknown(MediaFile $mediaFile, string $fullPath): array
    {
        Log::info("Processing unknown file type (basic metadata): {$mediaFile->original_filename}");

        $mimeType = $mediaFile->mime_type ?? mime_content_type($fullPath);

        return [
            'description' => "File: {$mediaFile->original_filename}",
            'detailed_description' => "File type: {$mimeType}",
            'meta_tags' => [pathinfo($mediaFile->original_filename, PATHINFO_EXTENSION)],
            'embedding' => null,
            'face_count' => 0,
            'face_encodings' => [],
            'faces' => [],
        ];
    }

    /**
     * Sanitize string for PostgreSQL to avoid Unicode escape sequence errors.
     *
     * @param string|null $text
     * @return string|null
     */
    private function sanitizeForPostgres(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Remove NULL bytes and other problematic characters
        $text = str_replace("\0", '', $text);
        
        // Remove invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove any remaining control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $text);

        return $text;
    }

    /**
     * Format bytes to human-readable size.
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}


<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ImageFile;
use App\Services\AiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ImageUploader extends Component
{
    use WithFileUploads;

    /**
     * Uploaded images.
     *
     * @var array
     */
    public $images = [];

    /**
     * Processing status.
     *
     * @var bool
     */
    public $processing = false;

    /**
     * Results from image analysis.
     *
     * @var array
     */
    public $results = [];

    /**
     * Error messages.
     *
     * @var array
     */
    public $errors_list = [];

    /**
     * Current progress.
     *
     * @var array
     */
    public $progress = [
        'current' => 0,
        'total' => 0,
        'percentage' => 0
    ];

    /**
     * Validation rules.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ];
    }

    /**
     * Updated images property.
     */
    public function updatedImages()
    {
        $this->validate();
    }

    /**
     * Process uploaded images.
     */
    public function processImages()
    {
        $this->validate();

        if (empty($this->images)) {
            $this->addError('images', 'Please select at least one image.');
            return;
        }

        $this->processing = true;
        $this->results = [];
        $this->errors_list = [];
        $this->progress = [
            'current' => 0,
            'total' => count($this->images),
            'percentage' => 0
        ];

        $aiService = app(AiService::class);

        // Check AI service health
        if (!$aiService->isHealthy()) {
            $this->processing = false;
            $this->addError('ai_service', 'AI service is not available. Please try again later.');
            return;
        }

        foreach ($this->images as $index => $image) {
            try {
                // Store the image
                $path = $image->store('public/images');
                $fullPath = Storage::path($path);

                Log::info('Processing uploaded image', [
                    'path' => $path,
                    'full_path' => $fullPath
                ]);

                // Extract metadata
                $metadata = $this->extractMetadata($fullPath, $image);

                // Analyze image with AI service
                $analysis = $aiService->analyzeImage($path);

                // Merge metadata with analysis
                $imageData = array_merge($metadata, [
                    'file_path' => $path,
                    'description' => $analysis['description'],
                    'detailed_description' => $analysis['detailed_description'] ?? null,
                    'meta_tags' => $analysis['meta_tags'] ?? [],
                    'face_count' => $analysis['face_count'] ?? 0,
                    'face_encodings' => $analysis['face_encodings'] ?? [],
                    'embedding' => $analysis['embedding'],
                ]);

                // Save to database
                $imageFile = ImageFile::create($imageData);

                // Add to results
                $this->results[] = [
                    'id' => $imageFile->id,
                    'filename' => $image->getClientOriginalName(),
                    'path' => $path,
                    'url' => Storage::url($path),
                    'description' => $analysis['description'],
                    'success' => true
                ];

                Log::info('Image processed successfully', [
                    'id' => $imageFile->id,
                    'filename' => $image->getClientOriginalName()
                ]);

            } catch (Exception $e) {
                Log::error('Failed to process image', [
                    'filename' => $image->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);

                $this->errors_list[] = [
                    'filename' => $image->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }

            // Update progress
            $this->progress['current'] = $index + 1;
            $this->progress['percentage'] = round(($this->progress['current'] / $this->progress['total']) * 100);
        }

        $this->processing = false;
        
        // Clear images after processing
        $this->images = [];
    }

    /**
     * Extract metadata from uploaded image.
     *
     * @param string $fullPath Full path to the image file
     * @param \Illuminate\Http\UploadedFile $uploadedFile Original uploaded file
     * @return array
     */
    protected function extractMetadata(string $fullPath, $uploadedFile): array
    {
        $metadata = [
            'original_filename' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getMimeType(),
            'file_size' => filesize($fullPath),
        ];

        // Get image dimensions
        try {
            $imageInfo = getimagesize($fullPath);
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
            }
        } catch (Exception $e) {
            Log::warning('Failed to get image dimensions', ['error' => $e->getMessage()]);
        }

        // Extract EXIF data (for JPEG images)
        try {
            if (function_exists('exif_read_data') && in_array($metadata['mime_type'], ['image/jpeg', 'image/jpg', 'image/tiff'])) {
                $exif = @exif_read_data($fullPath, null, true);
                
                if ($exif && is_array($exif)) {
                    // Store complete EXIF data
                    $metadata['exif_data'] = $exif;

                    // Extract common fields
                    if (isset($exif['IFD0']['Make'])) {
                        $metadata['camera_make'] = trim($exif['IFD0']['Make']);
                    }
                    if (isset($exif['IFD0']['Model'])) {
                        $metadata['camera_model'] = trim($exif['IFD0']['Model']);
                    }
                    if (isset($exif['EXIF']['LensModel'])) {
                        $metadata['lens_model'] = trim($exif['EXIF']['LensModel']);
                    }

                    // Date taken
                    if (isset($exif['EXIF']['DateTimeOriginal'])) {
                        try {
                            $metadata['date_taken'] = \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exif['EXIF']['DateTimeOriginal']);
                        } catch (Exception $e) {
                            Log::warning('Failed to parse date taken', ['error' => $e->getMessage()]);
                        }
                    } elseif (isset($exif['IFD0']['DateTime'])) {
                        try {
                            $metadata['date_taken'] = \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exif['IFD0']['DateTime']);
                        } catch (Exception $e) {
                            Log::warning('Failed to parse date time', ['error' => $e->getMessage()]);
                        }
                    }

                    // Exposure settings
                    if (isset($exif['EXIF']['ExposureTime'])) {
                        $metadata['exposure_time'] = $this->formatExposureTime($exif['EXIF']['ExposureTime']);
                    }
                    if (isset($exif['EXIF']['FNumber'])) {
                        $metadata['f_number'] = $this->formatFNumber($exif['EXIF']['FNumber']);
                    }
                    if (isset($exif['EXIF']['ISOSpeedRatings'])) {
                        $metadata['iso'] = is_array($exif['EXIF']['ISOSpeedRatings']) 
                            ? $exif['EXIF']['ISOSpeedRatings'][0] 
                            : $exif['EXIF']['ISOSpeedRatings'];
                    }
                    if (isset($exif['EXIF']['FocalLength'])) {
                        $metadata['focal_length'] = $this->evalFraction($exif['EXIF']['FocalLength']);
                    }

                    // GPS data
                    if (isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitude'])) {
                        $metadata['gps_latitude'] = $this->getGpsCoordinate($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef'] ?? 'N');
                        $metadata['gps_longitude'] = $this->getGpsCoordinate($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef'] ?? 'E');
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning('Failed to extract EXIF data', ['error' => $e->getMessage()]);
        }

        return $metadata;
    }

    /**
     * Format exposure time from EXIF fraction.
     */
    protected function formatExposureTime($exposureTime): string
    {
        if (is_string($exposureTime) && strpos($exposureTime, '/') !== false) {
            $parts = explode('/', $exposureTime);
            if (count($parts) == 2 && $parts[1] != 0) {
                $decimal = $parts[0] / $parts[1];
                if ($decimal >= 1) {
                    return round($decimal, 1) . 's';
                }
                return $exposureTime . 's';
            }
        }
        return $exposureTime;
    }

    /**
     * Format F-number from EXIF fraction.
     */
    protected function formatFNumber($fNumber): string
    {
        $value = $this->evalFraction($fNumber);
        return 'f/' . number_format($value, 1);
    }

    /**
     * Evaluate EXIF fraction to decimal.
     */
    protected function evalFraction($fraction): float
    {
        if (is_string($fraction) && strpos($fraction, '/') !== false) {
            $parts = explode('/', $fraction);
            if (count($parts) == 2 && $parts[1] != 0) {
                return $parts[0] / $parts[1];
            }
        }
        return is_numeric($fraction) ? (float)$fraction : 0;
    }

    /**
     * Convert GPS coordinates to decimal format.
     */
    protected function getGpsCoordinate(array $coordinate, string $ref): float
    {
        $degrees = $this->evalFraction($coordinate[0]);
        $minutes = $this->evalFraction($coordinate[1]);
        $seconds = $this->evalFraction($coordinate[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if (in_array($ref, ['S', 'W'])) {
            $decimal *= -1;
        }

        return round($decimal, 7);
    }

    /**
     * Clear all results and reset form.
     */
    public function clear()
    {
        $this->reset(['images', 'results', 'errors_list', 'progress']);
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.image-uploader')
            ->layout('layouts.app');
    }
}


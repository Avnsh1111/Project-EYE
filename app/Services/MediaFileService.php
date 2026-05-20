<?php

namespace App\Services;

use App\Models\MediaFile;
use App\Models\ImageFile;
use App\Models\VideoFile;
use App\Models\DocumentFile;
use App\Models\AudioFile;
use App\Models\ArchiveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Media File Service
 *
 * Handles all media file operations across different types:
 * - Image, Video, Document, Audio uploads
 * - Media-specific validation and processing
 * - Thumbnail generation
 * - File routing to appropriate processors
 */
class MediaFileService
{
    /**
     * Allowed MIME types by media category.
     */
    const ALLOWED_MIME_TYPES = [
        'image' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/svg+xml',
            // New formats
            'image/tiff',
            'image/x-icon',
            'image/avif',
            'image/heic',
            'image/heif',
        ],
        'video' => [
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-matroska',
            'video/webm',
            // New formats
            'video/x-flv',
            'video/x-ms-wmv',
            'video/3gpp',
            'video/ogg',
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            // New formats
            'application/rtf',
            'text/markdown',
            'text/x-markdown',
            'application/json',
            'application/xml',
            'text/xml',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'application/epub+zip',
        ],
        'design' => [
            // Adobe Design Files
            'image/vnd.adobe.photoshop',
            'application/x-photoshop',
            'application/photoshop',
            'application/psd',
            'image/psd',
            'image/x-psd',
            'application/illustrator',
            'application/postscript',
            'application/x-illustrator',
            'image/x-eps',
            'application/eps',
            'application/x-eps',
            // Other Design Formats
            'application/x-indesign',
            'application/x-sketch',
            'application/vnd.figma',
            'image/x-xcf',  // GIMP
            'application/x-krita',
            'image/x-exr',  // OpenEXR
            'application/x-blender',
            'application/x-coreldraw',
            'application/x-affinity',
            // CAD Files
            'application/x-autocad',
            'application/acad',
            'image/vnd.dwg',
            'image/vnd.dxf',
            'application/dxf',
            // 3D Model Files
            'model/obj',
            'model/fbx',
            'model/gltf+json',
            'model/gltf-binary',
            'application/x-3ds',
        ],
        'audio' => [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/wave',
            'audio/ogg',
            'audio/webm',
            'audio/flac',
            'audio/aac',
            'audio/m4a',
            // New formats
            'audio/aiff',
            'audio/x-aiff',
            'audio/opus',
            'audio/x-ms-wma',
            'audio/midi',
            'audio/x-midi',
        ],
        'archive' => [
            'application/zip',
            'application/x-rar-compressed',
            'application/vnd.rar',
            'application/x-tar',
            'application/x-7z-compressed',
            'application/gzip',
            'application/x-gzip',
        ],
        'code' => [
            // SQL & Database
            'application/sql',
            'application/x-sql',
            'text/x-sql',
            'application/x-sqlite3',
            'application/vnd.sqlite3',
            // Programming Languages
            'text/x-python',
            'application/x-python-code',
            'text/x-php',
            'application/x-php',
            'application/javascript',
            'text/javascript',
            'application/typescript',
            'text/typescript',
            'text/x-java-source',
            'text/x-c',
            'text/x-c++',
            'text/x-csharp',
            'text/x-go',
            'text/x-rust',
            'text/x-ruby',
            'text/x-swift',
            'text/x-kotlin',
            'text/x-scala',
            'text/x-perl',
            'text/x-r',
            // Web Technologies
            'text/html',
            'application/xhtml+xml',
            'text/css',
            'text/x-scss',
            'text/x-sass',
            'text/x-less',
            // Config & Data
            'application/x-yaml',
            'text/yaml',
            'text/x-yaml',
            'application/toml',
            'text/x-toml',
            'text/x-ini',
            'application/x-ini',
            'text/x-properties',
            'application/x-sh',
            'text/x-shellscript',
            // Shell Scripts
            'application/x-bash',
            'application/x-powershell',
            'application/x-bat',
        ],
    ];

    /**
     * Maximum file sizes in bytes by media type.
     */
    const MAX_FILE_SIZES = [
        'image' => 10485760,      // 10MB
        'video' => 524288000,     // 500MB
        'document' => 52428800,   // 50MB
        'code' => 10485760,       // 10MB
        'audio' => 104857600,     // 100MB
        'archive' => 524288000,   // 500MB
        'design' => 524288000,    // 500MB (design files can be large)
        'other' => 52428800,      // 50MB
    ];

    /**
     * Storage directories by media type.
     */
    const STORAGE_DIRECTORIES = [
        'image' => 'public/images',
        'video' => 'public/videos',
        'document' => 'public/documents',
        'code' => 'public/code',
        'audio' => 'public/audio',
        'archive' => 'public/archives',
        'design' => 'public/design',
        'other' => 'public/files',
        'thumbnails' => 'public/thumbnails',
        'waveforms' => 'public/waveforms',
    ];

    /**
     * Store an uploaded media file.
     *
     * @param UploadedFile $file
     * @param string $mediaType
     * @return array ['path' => string, 'full_path' => string, 'media_type' => string]
     * @throws \Exception
     */
    public function storeUploadedMedia(UploadedFile $file, ?string $mediaType = null): array
    {
        try {
            // Detect media type if not provided
            if (!$mediaType) {
                $mediaType = $this->detectMediaType($file);
            }

            // Validate file
            $this->validateMediaFile($file, $mediaType);

            // Store file in appropriate directory
            $directory = self::STORAGE_DIRECTORIES[$mediaType];
            $path = $file->store($directory);
            $fullPath = Storage::path($path);

            Log::info('Media file stored', [
                'media_type' => $mediaType,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            return [
                'path' => $path,
                'full_path' => $fullPath,
                'media_type' => $mediaType,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to store media file', [
                'original_name' => $file->getClientOriginalName(),
                'media_type' => $mediaType ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Detect media type from uploaded file.
     *
     * @param UploadedFile $file
     * @return string
     */
    public function detectMediaType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        foreach (self::ALLOWED_MIME_TYPES as $mediaType => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes)) {
                return $mediaType;
            }
        }

        // Unknown MIME types fall into 'other' category
        return 'other';
    }

    /**
     * Validate an uploaded media file.
     *
     * @param UploadedFile $file
     * @param string $mediaType
     * @return void
     * @throws \Exception
     */
    public function validateMediaFile(UploadedFile $file, string $mediaType): void
    {
        // Check if it's a valid file
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        // Check if media type is supported
        if (!isset(self::ALLOWED_MIME_TYPES[$mediaType])) {
            throw new \Exception("Unsupported media type: {$mediaType}");
        }

        // Check MIME type
        $allowedMimeTypes = self::ALLOWED_MIME_TYPES[$mediaType];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            $allowed = implode(', ', array_map(function($mime) {
                return explode('/', $mime)[1];
            }, $allowedMimeTypes));
            throw new \Exception("File type not allowed for {$mediaType}. Allowed: {$allowed}");
        }

        // Check file size
        $maxSize = self::MAX_FILE_SIZES[$mediaType];
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 0);
            throw new \Exception("File size exceeds maximum allowed size ({$maxSizeMB}MB for {$mediaType})");
        }
    }

    /**
     * Create media file model instance with basic info.
     *
     * @param array $fileData
     * @param UploadedFile $file
     * @return MediaFile
     */
    public function createMediaFileRecord(array $fileData, UploadedFile $file): MediaFile
    {
        $mediaType = $fileData['media_type'];
        $modelClass = $this->getModelClass($mediaType);

        return $modelClass::create([
            'user_id' => Auth::id(),
            'file_path' => $fileData['path'],
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'media_type' => $mediaType,
            'processing_status' => 'pending',
        ]);
    }

    /**
     * Get model class for media type.
     *
     * @param string $mediaType
     * @return string
     */
    protected function getModelClass(string $mediaType): string
    {
        return match($mediaType) {
            'image' => ImageFile::class,
            'video' => VideoFile::class,
            'document', 'code' => DocumentFile::class,
            'audio' => AudioFile::class,
            'archive' => ArchiveFile::class,
            'design' => DocumentFile::class,  // Store design files as documents
            'other' => DocumentFile::class,
            default => throw new \InvalidArgumentException("Unknown media type: {$mediaType}"),
        };
    }

    /**
     * Convert Laravel storage path to shared volume path (for Docker).
     *
     * @param string $laravelPath
     * @param string $mediaType
     * @return string
     */
    public function convertToSharedPath(string $laravelPath, string $mediaType = 'image'): string
    {
        // Configurable prefix: in Docker, points to /app/shared/; on host (Valet) it is
        // the absolute storage/app/public/ path, making shared paths real filesystem paths.
        $prefix = rtrim(config('ai.shared_prefix', storage_path('app/public')), '/') . '/';

        // Remove 'storage/app/public/{type}/' or 'public/{type}/' prefix
        $pattern = '#^(storage/app/)?public/(' . implode('|', array_keys(self::STORAGE_DIRECTORIES)) . ')/#';
        $relativePath = preg_replace($pattern, '', $laravelPath);

        return $prefix . $mediaType . 's/' . $relativePath;
    }

    /**
     * Get public URL for a media file.
     *
     * @param string $filePath
     * @return string
     */
    public function getPublicUrl(string $filePath): string
    {
        return asset('storage/' . str_replace('public/', '', $filePath));
    }

    /**
     * Delete a media file and its associated files (thumbnails, etc).
     *
     * @param MediaFile $mediaFile
     * @return bool
     */
    public function deleteMediaFile(MediaFile $mediaFile): bool
    {
        try {
            // Delete main file
            if (Storage::exists($mediaFile->file_path)) {
                Storage::delete($mediaFile->file_path);
            }

            // Delete thumbnail if exists
            if ($mediaFile->thumbnail_path && Storage::exists($mediaFile->thumbnail_path)) {
                Storage::delete($mediaFile->thumbnail_path);
            }

            // Delete audio waveform if exists
            if ($mediaFile->media_type === 'audio') {
                $waveformPath = 'public/waveforms/' . pathinfo($mediaFile->file_path, PATHINFO_FILENAME) . '.png';
                if (Storage::exists($waveformPath)) {
                    Storage::delete($waveformPath);
                }
            }

            Log::info('Media file deleted', [
                'id' => $mediaFile->id,
                'media_type' => $mediaFile->media_type,
                'path' => $mediaFile->file_path,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete media file', [
                'id' => $mediaFile->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get storage statistics by media type.
     *
     * @return array
     */
    public function getStorageStats(): array
    {
        $stats = [];

        foreach (['image', 'video', 'document', 'audio', 'archive', 'design'] as $mediaType) {
            $directory = self::STORAGE_DIRECTORIES[$mediaType];
            $files = Storage::allFiles($directory);

            $totalSize = 0;
            foreach ($files as $file) {
                $totalSize += Storage::size($file);
            }

            $stats[$mediaType] = [
                'total_files' => count($files),
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1048576, 2),
                'total_size_gb' => round($totalSize / 1073741824, 2),
            ];
        }

        return $stats;
    }

    /**
     * Check if file exists in storage.
     *
     * @param string $filePath
     * @return bool
     */
    public function fileExists(string $filePath): bool
    {
        return Storage::exists($filePath);
    }

    /**
     * Get full file system path for a storage path.
     *
     * @param string $storagePath
     * @return string
     */
    public function getFullPath(string $storagePath): string
    {
        return Storage::path($storagePath);
    }
}

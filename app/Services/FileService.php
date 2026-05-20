<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * File Service
 * 
 * Handles all file-related operations:
 * - File upload and storage
 * - File validation
 * - File deletion
 * - Path conversions
 */
class FileService
{
    /**
     * Allowed image MIME types.
     */
    const ALLOWED_MIME_TYPES = [
        // Accept all file types - validation handled at controller level
    ];

    /**
     * Maximum file size in bytes (500MB).
     */
    const MAX_FILE_SIZE = 524288000;

    /**
     * Storage directory for images.
     */
    const STORAGE_DIRECTORY = 'public/images';

    /**
     * Store an uploaded image file.
     *
     * @param UploadedFile $file
     * @return array ['path' => string, 'full_path' => string]
     * @throws \Exception
     */
    public function storeUploadedImage(UploadedFile $file): array
    {
        try {
            // Validate file
            $this->validateImageFile($file);
            
            // Store file
            $path = $file->store(self::STORAGE_DIRECTORY);
            $fullPath = Storage::path($path);
            
            Log::info('Image file stored', [
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'size' => $file->getSize()
            ]);
            
            return [
                'path' => $path,
                'full_path' => $fullPath,
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to store image file', [
                'original_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate an uploaded image file.
     *
     * @param UploadedFile $file
     * @return void
     * @throws \Exception
     */
    public function validateImageFile(UploadedFile $file): void
    {
        // Check if it's a valid file
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum allowed size (500MB)');
        }

        // MIME type validation removed - accept all file types
    }

    /**
     * Delete a file from storage.
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteFile(string $filePath): bool
    {
        try {
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
                Log::info('File deleted', ['path' => $filePath]);
                return true;
            }
            
            Log::warning('File not found for deletion', ['path' => $filePath]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Failed to delete file', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a file exists in storage.
     *
     * @param string $filePath
     * @return bool
     */
    public function fileExists(string $filePath): bool
    {
        return Storage::exists($filePath);
    }

    /**
     * Get the full file system path for a storage path.
     *
     * @param string $storagePath
     * @return string
     */
    public function getFullPath(string $storagePath): string
    {
        return Storage::path($storagePath);
    }

    /**
     * Convert Laravel storage path to shared volume path (for Docker).
     *
     * @param string $laravelPath Laravel storage path (e.g., 'public/images/abc.jpg')
     * @return string Docker volume path (e.g., '/app/shared/images/abc.jpg')
     */
    public function convertToSharedPath(string $laravelPath): string
    {
        $prefix = rtrim(config('ai.shared_prefix', '/app/shared/'), '/') . '/';

        $relativePath = preg_replace(
            '#^(storage/app/)?public/#',
            '',
            $laravelPath
        );

        return $prefix . $relativePath;
    }

    /**
     * Get public URL for an image.
     *
     * @param string $filePath
     * @return string
     */
    public function getPublicUrl(string $filePath): string
    {
        return asset('storage/' . str_replace('public/', '', $filePath));
    }

    /**
     * Get file information.
     *
     * @param string $filePath
     * @return array|null
     */
    public function getFileInfo(string $filePath): ?array
    {
        if (!$this->fileExists($filePath)) {
            return null;
        }
        
        $fullPath = $this->getFullPath($filePath);
        
        return [
            'size' => filesize($fullPath),
            'mime_type' => mime_content_type($fullPath),
            'last_modified' => filemtime($fullPath),
        ];
    }

    /**
     * Bulk delete files.
     *
     * @param array $filePaths
     * @return int Number of files deleted
     */
    public function bulkDeleteFiles(array $filePaths): int
    {
        $count = 0;
        
        foreach ($filePaths as $filePath) {
            if ($this->deleteFile($filePath)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get storage disk usage statistics.
     *
     * @return array
     */
    public function getStorageStats(): array
    {
        $directory = self::STORAGE_DIRECTORY;
        $files = Storage::allFiles($directory);
        
        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += Storage::size($file);
        }
        
        return [
            'total_files' => count($files),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1048576, 2),
            'total_size_gb' => round($totalSize / 1073741824, 2),
        ];
    }
}


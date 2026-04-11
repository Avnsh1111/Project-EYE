<?php

namespace App\Services;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ResumableUploadService
{
    private const CHUNK_SIZE    = 5 * 1024 * 1024; // 5 MB
    private const UPLOAD_TTL    = 3600 * 24;        // 24 hours
    private const TEMP_DISK     = 'local';
    private const TEMP_DIR      = 'resumable_uploads';

    public function initUpload(User $user, string $filename, int $totalBytes): array
    {
        $uploadId = Str::uuid()->toString();

        Cache::put("upload:{$uploadId}", [
            'user_id'      => $user->id,
            'filename'     => $filename,
            'total_bytes'  => $totalBytes,
            'received'     => 0,
        ], self::UPLOAD_TTL);

        return [
            'upload_id'  => $uploadId,
            'chunk_size' => self::CHUNK_SIZE,
        ];
    }

    public function appendChunk(string $uploadId, int $offset, string $data): array
    {
        $meta = Cache::get("upload:{$uploadId}");
        if (!$meta) {
            throw new RuntimeException("Upload session not found: {$uploadId}");
        }

        $tempPath = self::TEMP_DIR . "/{$uploadId}.tmp";

        // Write chunk at offset using fseek
        $fullPath = Storage::disk(self::TEMP_DISK)->path($tempPath);
        $dir      = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fh = fopen($fullPath, 'c+b');
        fseek($fh, $offset);
        fwrite($fh, $data);
        fclose($fh);

        $bytesWritten       = strlen($data);
        $meta['received']  += $bytesWritten;
        Cache::put("upload:{$uploadId}", $meta, self::UPLOAD_TTL);

        return ['received' => $bytesWritten];
    }

    public function finalise(string $uploadId, User $user): MediaFile
    {
        $meta = Cache::get("upload:{$uploadId}");
        if (!$meta) {
            throw new RuntimeException("Upload session not found: {$uploadId}");
        }

        $tempPath   = self::TEMP_DIR . "/{$uploadId}.tmp";
        $filename   = $meta['filename'];
        $ext        = pathinfo($filename, PATHINFO_EXTENSION);
        $storagePath = 'uploads/' . $user->id . '/' . Str::uuid() . '.' . $ext;

        // Move temp file to final location
        $tempFullPath = Storage::disk(self::TEMP_DISK)->path($tempPath);
        $destFullPath = Storage::disk(self::TEMP_DISK)->path($storagePath);
        $destDir      = dirname($destFullPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        rename($tempFullPath, $destFullPath);

        $fileSize = filesize($destFullPath);
        $mimeType = mime_content_type($destFullPath) ?: 'application/octet-stream';
        $mediaType = $this->detectMediaType($mimeType);

        $mediaFile = MediaFile::withoutGlobalScopes()->create([
            'user_id'           => $user->id,
            'original_filename' => $filename,
            'file_path'         => $storagePath,
            'media_type'        => $mediaType,
            'mime_type'         => $mimeType,
            'file_size'         => $fileSize,
            'processing_status' => 'pending',
        ]);

        Cache::forget("upload:{$uploadId}");

        return $mediaFile;
    }

    private function detectMediaType(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/')       => 'image',
            str_starts_with($mimeType, 'video/')       => 'video',
            str_starts_with($mimeType, 'audio/')       => 'audio',
            str_contains($mimeType,   'pdf')           => 'document',
            str_contains($mimeType,   'zip')
                || str_contains($mimeType, 'rar')      => 'archive',
            default                                    => 'document',
        };
    }
}

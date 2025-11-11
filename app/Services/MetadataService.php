<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Metadata Service
 * 
 * Handles extraction of metadata from image files including:
 * - Basic file info (dimensions, size, mime type)
 * - EXIF data (camera, exposure, GPS)
 * - Quick metadata for instant uploads
 */
class MetadataService
{
    /**
     * Supported JPEG MIME types for EXIF extraction.
     */
    const JPEG_MIME_TYPES = ['image/jpeg', 'image/jpg'];

    /**
     * Extract quick metadata for instant upload (non-blocking, fast fields only).
     *
     * @param string $fullPath Full file system path
     * @param mixed $uploadedFile Uploaded file instance
     * @return array
     */
    public function extractQuickMetadata(string $fullPath, $uploadedFile): array
    {
        $metadata = $this->getBasicFileInfo($fullPath, $uploadedFile);
        
        // Add dimensions
        $dimensions = $this->getImageDimensions($fullPath);
        if ($dimensions) {
            $metadata = array_merge($metadata, $dimensions);
        }
        
        // Add basic EXIF (fast fields only)
        $exif = $this->getBasicExifData($fullPath, $metadata['mime_type']);
        if ($exif) {
            $metadata = array_merge($metadata, $exif);
        }
        
        return $metadata;
    }

    /**
     * Extract comprehensive metadata (for background processing).
     *
     * @param string $fullPath Full file system path
     * @return array
     */
    public function extractComprehensiveMetadata(string $fullPath): array
    {
        $metadata = [];
        
        // Basic file info
        if (file_exists($fullPath)) {
            $metadata['file_size'] = filesize($fullPath);
            $metadata['mime_type'] = mime_content_type($fullPath);
        }
        
        // Dimensions
        $dimensions = $this->getImageDimensions($fullPath);
        if ($dimensions) {
            $metadata = array_merge($metadata, $dimensions);
        }
        
        // Full EXIF data
        $exif = $this->getFullExifData($fullPath, $metadata['mime_type'] ?? null);
        if ($exif) {
            $metadata = array_merge($metadata, $exif);
        }
        
        return $metadata;
    }

    /**
     * Get basic file information.
     *
     * @param string $fullPath
     * @param mixed $uploadedFile
     * @return array
     */
    protected function getBasicFileInfo(string $fullPath, $uploadedFile): array
    {
        return [
            'original_filename' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getMimeType(),
            'file_size' => @filesize($fullPath) ?: 0,
        ];
    }

    /**
     * Get image dimensions.
     *
     * @param string $fullPath
     * @return array|null
     */
    protected function getImageDimensions(string $fullPath): ?array
    {
        try {
            $imageInfo = @getimagesize($fullPath);
            if ($imageInfo) {
                return [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }
        } catch (\Exception $e) {
            Log::debug('Failed to get image dimensions', ['error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Get basic EXIF data (fast fields only).
     *
     * @param string $fullPath
     * @param string $mimeType
     * @return array|null
     */
    protected function getBasicExifData(string $fullPath, string $mimeType): ?array
    {
        if (!$this->supportsExif($mimeType)) {
            return null;
        }
        
        try {
            $exif = @exif_read_data($fullPath, 'IFD0', true);
            
            if (!$exif || !is_array($exif)) {
                return null;
            }
            
            $metadata = [];
            
            // Camera info
            if (isset($exif['IFD0']['Make'])) {
                $metadata['camera_make'] = $this->sanitizeString($exif['IFD0']['Make']);
            }
            if (isset($exif['IFD0']['Model'])) {
                $metadata['camera_model'] = $this->sanitizeString($exif['IFD0']['Model']);
            }
            
            // Date taken
            if (isset($exif['EXIF']['DateTimeOriginal'])) {
                $metadata['date_taken'] = $this->parseExifDate($exif['EXIF']['DateTimeOriginal']);
            }
            
            return $metadata;
            
        } catch (\Exception $e) {
            Log::debug('Failed to read basic EXIF', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get full EXIF data (comprehensive, for background processing).
     *
     * @param string $fullPath
     * @param string|null $mimeType
     * @return array|null
     */
    protected function getFullExifData(string $fullPath, ?string $mimeType): ?array
    {
        if (!$mimeType || !$this->supportsExif($mimeType)) {
            return null;
        }
        
        try {
            $exif = @exif_read_data($fullPath, 0, true);
            
            if (!$exif || !is_array($exif)) {
                return null;
            }
            
            $metadata = [];
            
            // Camera information
            $metadata = array_merge($metadata, $this->extractCameraInfo($exif));
            
            // Exposure settings
            $metadata = array_merge($metadata, $this->extractExposureSettings($exif));
            
            // GPS data
            $metadata = array_merge($metadata, $this->extractGpsData($exif));
            
            // Store raw EXIF for reference
            $metadata['exif_data'] = $this->cleanExifData($exif);
            
            return $metadata;
            
        } catch (\Exception $e) {
            Log::debug('Failed to read full EXIF', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract camera information from EXIF.
     *
     * @param array $exif
     * @return array
     */
    protected function extractCameraInfo(array $exif): array
    {
        $metadata = [];
        
        if (isset($exif['IFD0']['Make'])) {
            $metadata['camera_make'] = $this->sanitizeString($exif['IFD0']['Make']);
        }
        if (isset($exif['IFD0']['Model'])) {
            $metadata['camera_model'] = $this->sanitizeString($exif['IFD0']['Model']);
        }
        if (isset($exif['EXIF']['LensModel'])) {
            $metadata['lens_model'] = $this->sanitizeString($exif['EXIF']['LensModel']);
        }
        if (isset($exif['EXIF']['DateTimeOriginal'])) {
            $metadata['date_taken'] = $this->parseExifDate($exif['EXIF']['DateTimeOriginal']);
        }
        
        return $metadata;
    }

    /**
     * Extract exposure settings from EXIF.
     *
     * @param array $exif
     * @return array
     */
    protected function extractExposureSettings(array $exif): array
    {
        $metadata = [];
        
        if (isset($exif['EXIF']['ExposureTime'])) {
            $metadata['exposure_time'] = $this->formatExposureTime($exif['EXIF']['ExposureTime']);
        }
        if (isset($exif['EXIF']['FNumber'])) {
            $metadata['f_number'] = $this->formatFNumber($exif['EXIF']['FNumber']);
        }
        if (isset($exif['EXIF']['ISOSpeedRatings'])) {
            $metadata['iso'] = $exif['EXIF']['ISOSpeedRatings'];
        }
        if (isset($exif['EXIF']['FocalLength'])) {
            $metadata['focal_length'] = $this->formatFocalLength($exif['EXIF']['FocalLength']);
        }
        
        return $metadata;
    }

    /**
     * Extract GPS data from EXIF.
     *
     * @param array $exif
     * @return array
     */
    protected function extractGpsData(array $exif): array
    {
        $metadata = [];
        
        if (isset($exif['GPS']) && is_array($exif['GPS'])) {
            $gpsData = $exif['GPS'];
            
            $lat = $this->getGps($gpsData['GPSLatitude'] ?? null, $gpsData['GPSLatitudeRef'] ?? 'N');
            $lon = $this->getGps($gpsData['GPSLongitude'] ?? null, $gpsData['GPSLongitudeRef'] ?? 'E');
            
            if ($lat !== null && $lon !== null) {
                $metadata['gps_latitude'] = $lat;
                $metadata['gps_longitude'] = $lon;
            }
        }
        
        return $metadata;
    }

    /**
     * Check if file supports EXIF data extraction.
     *
     * @param string $mimeType
     * @return bool
     */
    protected function supportsExif(string $mimeType): bool
    {
        return function_exists('exif_read_data') && in_array($mimeType, self::JPEG_MIME_TYPES);
    }

    /**
     * Parse EXIF date string to Carbon instance.
     *
     * @param string $dateString
     * @return Carbon|null
     */
    protected function parseExifDate(string $dateString): ?Carbon
    {
        try {
            return Carbon::createFromFormat('Y:m:d H:i:s', $dateString);
        } catch (\Exception $e) {
            Log::debug('Failed to parse EXIF date', ['date' => $dateString, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Format exposure time for display.
     *
     * @param string $value
     * @return string
     */
    protected function formatExposureTime(string $value): string
    {
        if (str_contains($value, '/')) {
            return $value . 's';
        }
        return $value;
    }

    /**
     * Format f-number for display.
     *
     * @param string $value
     * @return string
     */
    protected function formatFNumber(string $value): string
    {
        if (str_contains($value, '/')) {
            $parts = explode('/', $value);
            if (count($parts) === 2 && $parts[1] != 0) {
                $fNumber = $parts[0] / $parts[1];
                return 'f/' . number_format($fNumber, 1);
            }
        }
        return 'f/' . $value;
    }

    /**
     * Format focal length for display.
     *
     * @param string $value
     * @return float|null
     */
    protected function formatFocalLength(string $value): ?float
    {
        if (str_contains($value, '/')) {
            $parts = explode('/', $value);
            if (count($parts) === 2 && $parts[1] != 0) {
                return round($parts[0] / $parts[1], 1);
            }
        }
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Convert GPS coordinates to decimal degrees.
     *
     * @param array|null $coordinate
     * @param string $hemisphere
     * @return float|null
     */
    protected function getGps(?array $coordinate, string $hemisphere): ?float
    {
        if (!$coordinate || count($coordinate) !== 3) {
            return null;
        }
        
        try {
            $degrees = $this->evaluateFraction($coordinate[0]);
            $minutes = $this->evaluateFraction($coordinate[1]);
            $seconds = $this->evaluateFraction($coordinate[2]);
            
            $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
            
            if ($hemisphere === 'S' || $hemisphere === 'W') {
                $decimal *= -1;
            }
            
            return $decimal;
        } catch (\Exception $e) {
            Log::debug('Failed to parse GPS coordinate', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Evaluate a fraction string to decimal.
     *
     * @param string $fraction
     * @return float
     */
    protected function evaluateFraction(string $fraction): float
    {
        if (str_contains($fraction, '/')) {
            $parts = explode('/', $fraction);
            if (count($parts) === 2 && $parts[1] != 0) {
                return $parts[0] / $parts[1];
            }
        }
        return (float) $fraction;
    }

    /**
     * Clean EXIF data for storage (remove binary data and sanitize strings).
     *
     * @param array $exif
     * @return array
     */
    protected function cleanExifData(array $exif): array
    {
        $cleaned = [];
        
        foreach ($exif as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = $this->cleanExifData($value);
            } elseif (is_string($value)) {
                // Sanitize string values to remove null bytes and control characters
                $cleaned[$key] = $this->sanitizeString($value);
            } elseif (is_numeric($value)) {
                // Store numeric values as-is
                $cleaned[$key] = $value;
            }
        }
        
        return $cleaned;
    }

    /**
     * Sanitize string for PostgreSQL to avoid Unicode escape sequence errors.
     *
     * @param string $value
     * @return string
     */
    protected function sanitizeString(string $value): string
    {
        // Remove NULL bytes
        $value = str_replace("\0", '', $value);
        
        // Remove invalid UTF-8 sequences
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        
        // Remove control characters except newlines and tabs
        $value = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Trim whitespace
        return trim($value);
    }

    /**
     * Format file size in human-readable format.
     *
     * @param int $bytes
     * @return string
     */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}


<?php

namespace App\Models;

class ArchiveFile extends MediaFile
{
    /**
     * Get the media type for archive.
     *
     * @return string
     */
    protected function getMediaType(): string
    {
        return 'archive';
    }

    /**
     * Get the thumbnail URL (archive icon).
     *
     * @return string
     */
    public function getThumbnailUrlAttribute(): string
    {
        // Archive SVG data URI (consistent icon for all archive types)
        return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#34a853"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-1 6h-3v3h-2v-3h-3v-2h3V7h2v3h3v2z"/></svg>');
    }

    /**
     * Get archive type/format based on compression type.
     *
     * @return string
     */
    public function getArchiveTypeAttribute(): string
    {
        return match($this->compression_type) {
            'zip' => 'ZIP Archive',
            'rar' => 'RAR Archive',
            'tar' => 'TAR Archive',
            'gzip' => 'GZIP Archive',
            '7z' => '7-Zip Archive',
            default => 'Archive',
        };
    }

    /**
     * Get compression ratio (percentage).
     *
     * @return float|null
     */
    public function getCompressionRatioAttribute(): ?float
    {
        if (!$this->file_size || !$this->uncompressed_size) {
            return null;
        }

        return round((1 - ($this->file_size / $this->uncompressed_size)) * 100, 1);
    }

    /**
     * Get formatted uncompressed size.
     *
     * @return string|null
     */
    public function getUncompressedSizeFormattedAttribute(): ?string
    {
        if (!$this->uncompressed_size) {
            return null;
        }

        $size = $this->uncompressed_size;

        if ($size >= 1073741824) {
            return round($size / 1073741824, 2) . ' GB';
        } elseif ($size >= 1048576) {
            return round($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            return round($size / 1024, 2) . ' KB';
        }

        return $size . ' bytes';
    }

    /**
     * Get archive technical info.
     *
     * @return array
     */
    public function getTechnicalInfoAttribute(): array
    {
        $info = [];

        if ($this->compression_type) {
            $info['type'] = strtoupper($this->compression_type);
        }

        if ($this->file_count) {
            $info['files'] = $this->file_count . ' files';
        }

        if ($this->compression_ratio) {
            $info['compression'] = $this->compression_ratio . '%';
        }

        if ($this->uncompressed_size) {
            $info['uncompressed'] = $this->uncompressed_size_formatted;
        }

        return $info;
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Check if archive has a password.
     *
     * @return bool
     */
    public function hasPassword(): bool
    {
        return $this->is_encrypted ?? false;
    }

    /**
     * List contents of the archive.
     *
     * @return array
     */
    public function listContents(): array
    {
        if (!$this->file_list) {
            return [];
        }

        return is_array($this->file_list) ? $this->file_list : json_decode($this->file_list, true) ?? [];
    }

    /**
     * Get file types distribution in archive.
     *
     * @return array
     */
    public function getFileTypesDistributionAttribute(): array
    {
        $contents = $this->listContents();

        if (empty($contents)) {
            return [];
        }

        $distribution = [];

        foreach ($contents as $file) {
            $extension = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
            $extension = strtolower($extension) ?: 'no extension';

            if (!isset($distribution[$extension])) {
                $distribution[$extension] = 0;
            }
            $distribution[$extension]++;
        }

        arsort($distribution);
        return $distribution;
    }

    /**
     * Search within archive contents.
     *
     * @param string $query
     * @return array
     */
    public function searchInContents(string $query): array
    {
        $contents = $this->listContents();

        if (empty($contents)) {
            return [];
        }

        $query = strtolower($query);
        $matches = [];

        foreach ($contents as $file) {
            $filename = $file['name'] ?? '';
            if (stripos($filename, $query) !== false) {
                $matches[] = $file;
            }
        }

        return $matches;
    }

    /**
     * Get total number of files in archive.
     *
     * @return int
     */
    public function getTotalFilesAttribute(): int
    {
        return $this->file_count ?? 0;
    }
}

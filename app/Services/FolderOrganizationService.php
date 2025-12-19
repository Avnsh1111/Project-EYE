<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\MediaFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FolderOrganizationService
{
    /**
     * Organize a media file into appropriate folders.
     */
    public function organizeFile(MediaFile $file): void
    {
        try {
            // Primary organization: By date taken or created
            $date = $file->date_taken ?? $file->created_at;
            $year = $date->format('Y');
            $month = $date->format('F'); // Full month name
            
            // Get or create year folder
            $yearFolder = $this->getOrCreateFolder(
                name: $year,
                path: $year,
                type: 'year',
                icon: 'calendar_today'
            );
            
            // Get or create month folder under year
            $monthFolder = $this->getOrCreateFolder(
                name: $month,
                path: "{$year}/{$month}",
                type: 'month',
                parentId: $yearFolder->id,
                icon: 'event'
            );
            
            // Assign the file to the month folder
            $file->folder_id = $monthFolder->id;
            $file->save();
            
            // Update statistics
            $monthFolder->updateStatistics();
            $yearFolder->updateStatistics();
            
        } catch (\Exception $e) {
            Log::error('Failed to organize file into folders', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get or create a folder.
     */
    public function getOrCreateFolder(
        string $name,
        string $path,
        string $type = 'custom',
        ?int $parentId = null,
        ?string $icon = null,
        ?string $description = null
    ): Folder {
        return Folder::firstOrCreate(
            ['path' => $path],
            [
                'name' => $name,
                'parent_id' => $parentId,
                'type' => $type,
                'icon' => $icon ?? $this->getDefaultIcon($type),
                'description' => $description,
            ]
        );
    }

    /**
     * Organize all unorganized files.
     */
    public function organizeAllFiles(): int
    {
        $unorganizedFiles = MediaFile::whereNull('folder_id')
            ->where('processing_status', 'completed')
            ->get();
        
        $count = 0;
        foreach ($unorganizedFiles as $file) {
            $this->organizeFile($file);
            $count++;
        }
        
        return $count;
    }

    /**
     * Create media type folders (Photos, Videos, Documents).
     */
    public function createMediaTypeFolders(): void
    {
        $types = [
            ['name' => 'Photos', 'path' => 'Photos', 'icon' => 'photo_library', 'type' => 'media_type'],
            ['name' => 'Videos', 'path' => 'Videos', 'icon' => 'videocam', 'type' => 'media_type'],
            ['name' => 'Documents', 'path' => 'Documents', 'icon' => 'description', 'type' => 'media_type'],
            ['name' => 'Audio', 'path' => 'Audio', 'icon' => 'audiotrack', 'type' => 'media_type'],
        ];

        foreach ($types as $typeData) {
            $this->getOrCreateFolder(
                name: $typeData['name'],
                path: $typeData['path'],
                type: $typeData['type'],
                icon: $typeData['icon']
            );
        }
    }

    /**
     * Get folder tree structure.
     */
    public function getFolderTree(): array
    {
        return Folder::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($folder) {
                return $this->mapFolderToTree($folder);
            })
            ->toArray();
    }

    /**
     * Map folder to tree structure recursively.
     */
    private function mapFolderToTree(Folder $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'path' => $folder->path,
            'type' => $folder->type,
            'icon' => $folder->icon,
            'file_count' => $folder->file_count,
            'total_size' => $folder->total_size,
            'children' => $folder->children->map(function ($child) {
                return $this->mapFolderToTree($child);
            })->toArray(),
        ];
    }

    /**
     * Get default icon for folder type.
     */
    private function getDefaultIcon(string $type): string
    {
        return match ($type) {
            'year' => 'calendar_today',
            'month' => 'event',
            'media_type' => 'folder',
            'event' => 'celebration',
            default => 'folder',
        };
    }

    /**
     * Get recent folders.
     */
    public function getRecentFolders(int $limit = 10): array
    {
        return Folder::where('file_count', '>', 0)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($folder) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'path' => $folder->path,
                    'icon' => $folder->icon,
                    'file_count' => $folder->file_count,
                    'total_size' => $this->formatSize($folder->total_size),
                    'updated_at' => $folder->updated_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Format file size for display.
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}


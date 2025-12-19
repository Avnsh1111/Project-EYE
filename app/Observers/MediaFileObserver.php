<?php

namespace App\Observers;

use App\Models\MediaFile;
use App\Services\FolderOrganizationService;
use Illuminate\Support\Facades\Log;

class MediaFileObserver
{
    protected FolderOrganizationService $folderService;

    public function __construct(FolderOrganizationService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Handle the MediaFile "created" event.
     */
    public function created(MediaFile $mediaFile): void
    {
        // Organize the file when it's created
        if (!$mediaFile->folder_id) {
            try {
                $this->folderService->organizeFile($mediaFile);
                Log::info('Auto-organized new media file', [
                    'file_id' => $mediaFile->id,
                    'folder_id' => $mediaFile->folder_id,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to auto-organize media file', [
                    'file_id' => $mediaFile->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the MediaFile "updated" event.
     */
    public function updated(MediaFile $mediaFile): void
    {
        // Re-organize if date_taken changed and folder exists
        if ($mediaFile->isDirty('date_taken') && $mediaFile->folder_id) {
            try {
                $this->folderService->organizeFile($mediaFile);
                Log::info('Re-organized media file after date change', [
                    'file_id' => $mediaFile->id,
                    'folder_id' => $mediaFile->folder_id,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to re-organize media file', [
                    'file_id' => $mediaFile->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the MediaFile "deleting" event.
     */
    public function deleting(MediaFile $mediaFile): void
    {
        // Update folder statistics when file is deleted
        if ($mediaFile->folder_id) {
            $folder = $mediaFile->folder;
            if ($folder) {
                $folder->file_count = max(0, $folder->file_count - 1);
                $folder->total_size = max(0, $folder->total_size - ($mediaFile->file_size ?? 0));
                $folder->save();
            }
        }
    }

    /**
     * Handle the MediaFile "restored" event.
     */
    public function restored(MediaFile $mediaFile): void
    {
        // Update folder statistics when file is restored
        if ($mediaFile->folder_id) {
            $folder = $mediaFile->folder;
            if ($folder) {
                $folder->updateStatistics();
            }
        }
    }
}

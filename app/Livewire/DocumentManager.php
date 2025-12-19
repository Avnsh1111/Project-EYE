<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\MediaFile;
use App\Services\ImageService;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentManager extends Component
{
    /**
     * Service instances.
     */
    protected ImageService $imageService;
    protected ImageRepository $imageRepository;

    /**
     * Boot the component.
     */
    public function boot(
        ImageService $imageService,
        ImageRepository $imageRepository
    ) {
        $this->imageService = $imageService;
        $this->imageRepository = $imageRepository;
    }

    public $files = [];
    public $folders = [];
    public $currentFolder = null;
    public $currentFolderId = null;
    public $breadcrumbs = [];
    public $selectedFile = null;
    public $searchQuery = '';
    public $viewMode = 'grid'; // grid or list
    public $sortBy = 'name'; // name, date, size, type
    public $sortDirection = 'asc';
    public $showTrash = false;
    
    // Selection mode
    public $selectionMode = false;
    public $selectedIds = [];
    
    // Stats
    public $stats = [
        'total_documents' => 0,
        'total_videos' => 0,
        'total_audio' => 0,
        'total_archives' => 0,
        'total_size' => 0,
    ];

    public function mount()
    {
        $this->loadFiles();
        $this->loadFolders();
        $this->loadStats();
    }

    public function loadFiles()
    {
        // Load all non-image files (documents, videos, audio, archives, etc.)
        $query = MediaFile::whereNotIn('media_type', ['image']);
        
        // Filter by current folder if set
        if ($this->currentFolderId) {
            $query->where('folder_id', $this->currentFolderId);
        }
        
        // Apply trash filter
        if ($this->showTrash) {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');
        }
        
        // Apply search filter
        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('original_filename', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('description', 'like', '%' . $this->searchQuery . '%');
            });
        }
        
        // Apply sorting
        switch ($this->sortBy) {
            case 'name':
                $query->orderBy('original_filename', $this->sortDirection);
                break;
            case 'date':
                $query->orderBy('created_at', $this->sortDirection);
                break;
            case 'size':
                $query->orderBy('file_size', $this->sortDirection);
                break;
            case 'type':
                $query->orderBy('media_type', $this->sortDirection)
                      ->orderBy('mime_type', $this->sortDirection);
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
        
        $files = $query->get();
        
        // Transform files for display
        $this->files = $files->map(function ($file) {
            return [
                'id' => $file->id,
                'name' => $file->original_filename ?? basename($file->file_path),
                'type' => $file->media_type,
                'mime_type' => $file->mime_type,
                'size' => $file->file_size,
                'size_human' => $this->formatFileSize($file->file_size),
                'created_at' => $file->created_at,
                'updated_at' => $file->updated_at,
                'thumbnail_url' => $file->thumbnail_path 
                    ? asset('storage/' . str_replace('public/', '', $file->thumbnail_path))
                    : $this->getFileTypeIcon($file->media_type),
                'file_url' => asset('storage/' . str_replace('public/', '', $file->file_path)),
                'is_favorite' => $file->is_favorite ?? false,
                'description' => $file->description,
                // Video specific
                'duration' => $file->duration_seconds,
                'resolution' => $file->resolution,
                // Document specific
                'page_count' => $file->page_count,
            ];
        })->toArray();
    }

    public function loadFolders()
    {
        // Load actual folders from database
        $query = \App\Models\Folder::with('mediaFiles');
        
        // If current folder is set, load only its children
        if ($this->currentFolderId) {
            $query->where('parent_id', $this->currentFolderId);
        } else {
            // Load root folders only
            $query->whereNull('parent_id');
        }
        
        $this->folders = $query->get()->map(function ($folder) {
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'path' => $folder->path,
                'type' => $folder->type,
                'icon' => $folder->icon ?? 'folder',
                'file_count' => $folder->file_count,
                'total_size' => $this->formatSize($folder->total_size),
                'updated_at' => $folder->updated_at?->diffForHumans(),
            ];
        })->toArray();
        
        // Load breadcrumbs if in a folder
        if ($this->currentFolderId) {
            $folder = \App\Models\Folder::find($this->currentFolderId);
            $this->breadcrumbs = $folder ? $folder->getBreadcrumbs() : [];
        } else {
            $this->breadcrumbs = [];
        }
    }
    
    public function openFolder($folderId)
    {
        $this->currentFolderId = $folderId;
        $this->loadFolders();
        $this->loadFiles();
    }
    
    public function goToFolder($folderId)
    {
        $this->currentFolderId = $folderId;
        $this->loadFolders();
        $this->loadFiles();
    }
    
    public function goToRoot()
    {
        $this->currentFolderId = null;
        $this->breadcrumbs = [];
        $this->loadFolders();
        $this->loadFiles();
    }
    
    protected function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function loadStats()
    {
        $documents = MediaFile::where('media_type', 'document')->whereNull('deleted_at')->count();
        $videos = MediaFile::where('media_type', 'video')->whereNull('deleted_at')->count();
        $audio = MediaFile::where('media_type', 'audio')->whereNull('deleted_at')->count();
        $archives = MediaFile::where('media_type', 'archive')->whereNull('deleted_at')->count();
        $totalSize = MediaFile::whereNotIn('media_type', ['image'])->whereNull('deleted_at')->sum('file_size');
        
        $this->stats = [
            'total_documents' => $documents,
            'total_videos' => $videos,
            'total_audio' => $audio,
            'total_archives' => $archives,
            'total_size' => $this->formatFileSize($totalSize),
        ];
    }

    public function selectFile($fileId)
    {
        $this->selectedFile = collect($this->files)->firstWhere('id', $fileId);
    }

    public function closeDetails()
    {
        $this->selectedFile = null;
    }

    public function toggleFavorite($fileId)
    {
        $file = MediaFile::find($fileId);
        if ($file) {
            $file->is_favorite = !$file->is_favorite;
            $file->save();
            $this->loadFiles();
        }
    }

    public function downloadFile($fileId)
    {
        $file = MediaFile::find($fileId);
        if ($file) {
            return Storage::download($file->file_path, $file->original_filename);
        }
    }

    public function deleteFile($fileId)
    {
        $file = MediaFile::find($fileId);
        if ($file) {
            $file->delete();
            $this->loadFiles();
            $this->loadStats();
            $this->closeDetails();
            session()->flash('message', 'File moved to trash.');
        }
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function setSortBy($sort)
    {
        if ($this->sortBy === $sort) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sort;
            $this->sortDirection = 'asc';
        }
        $this->loadFiles();
    }

    public function updatedSearchQuery()
    {
        $this->loadFiles();
    }

    public function toggleTrash()
    {
        $this->showTrash = !$this->showTrash;
        $this->loadFiles();
    }

    public function toggleSelectionMode()
    {
        $this->selectionMode = !$this->selectionMode;
        $this->selectedIds = [];
    }

    public function toggleSelection($fileId)
    {
        if (in_array($fileId, $this->selectedIds)) {
            $this->selectedIds = array_diff($this->selectedIds, [$fileId]);
        } else {
            $this->selectedIds[] = $fileId;
        }
    }

    public function selectAll()
    {
        $this->selectedIds = collect($this->files)->pluck('id')->toArray();
    }

    public function deselectAll()
    {
        $this->selectedIds = [];
    }

    public function restoreFile($fileId)
    {
        $file = MediaFile::withTrashed()->find($fileId);
        if ($file && $file->trashed()) {
            $file->restore();
            $this->loadFiles();
            $this->loadStats();
            $this->closeDetails();
            session()->flash('message', 'File restored from trash.');
        }
    }

    public function forceDeleteFile($fileId)
    {
        $file = MediaFile::withTrashed()->find($fileId);
        if ($file) {
            // Delete physical file
            try {
                Storage::delete($file->file_path);
                if ($file->thumbnail_path) {
                    Storage::delete($file->thumbnail_path);
                }
            } catch (\Exception $e) {
                Log::error('Failed to delete physical file', ['error' => $e->getMessage()]);
            }
            
            $file->forceDelete();
            $this->loadFiles();
            $this->loadStats();
            $this->closeDetails();
            session()->flash('message', 'File permanently deleted.');
        }
    }

    public function bulkDelete()
    {
        MediaFile::whereIn('id', $this->selectedIds)->delete();
        session()->flash('message', count($this->selectedIds) . ' files moved to trash.');
        $this->selectedIds = [];
        $this->selectionMode = false;
        $this->loadFiles();
        $this->loadStats();
    }

    public function bulkRestore()
    {
        MediaFile::whereIn('id', $this->selectedIds)->restore();
        session()->flash('message', count($this->selectedIds) . ' files restored.');
        $this->selectedIds = [];
        $this->selectionMode = false;
        $this->loadFiles();
        $this->loadStats();
    }

    public function bulkForceDelete()
    {
        $files = MediaFile::withTrashed()->whereIn('id', $this->selectedIds)->get();
        foreach ($files as $file) {
            try {
                Storage::delete($file->file_path);
                if ($file->thumbnail_path) {
                    Storage::delete($file->thumbnail_path);
                }
            } catch (\Exception $e) {
                Log::error('Failed to delete physical file', ['error' => $e->getMessage()]);
            }
            $file->forceDelete();
        }
        session()->flash('message', count($this->selectedIds) . ' files permanently deleted.');
        $this->selectedIds = [];
        $this->selectionMode = false;
        $this->loadFiles();
        $this->loadStats();
    }

    private function formatFileSize($bytes)
    {
        if (!$bytes || $bytes <= 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function getFileTypeIcon($type)
    {
        $icons = [
            'video' => '🎥',
            'audio' => '🎵',
            'document' => '📄',
            'archive' => '📦',
            'code' => '💻',
            'email' => '📧',
            'other' => '📎',
        ];
        
        return $icons[$type] ?? '📎';
    }

    private function getFolderIcon($type)
    {
        $icons = [
            'video' => 'movie',
            'audio' => 'audio_file',
            'document' => 'description',
            'archive' => 'folder_zip',
            'code' => 'code',
            'email' => 'email',
        ];
        
        return $icons[$type] ?? 'folder';
    }

    public function render()
    {
        return view('livewire.document-manager')->layout('layouts.app');
    }
}


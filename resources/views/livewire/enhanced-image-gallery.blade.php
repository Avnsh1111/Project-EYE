<div x-data="{ 
    showEditor: false,
    viewMode: 'grid',
    showImageDetails: false,
    selectedImage: null
}" 
x-on:keydown.window="
    if (event.key === 'ArrowRight' && $wire.selectedImage && $wire.nextFileId) {
        event.preventDefault();
        $wire.viewDetails($wire.nextFileId);
    } else if (event.key === 'ArrowLeft' && $wire.selectedImage && $wire.previousFileId) {
        event.preventDefault();
        $wire.viewDetails($wire.previousFileId);
    } else if (event.key === 'Escape') {
        event.preventDefault();
        if ($wire.selectedImage) {
            $wire.closeDetails();
        }
        if ($wire.selectionMode) {
            $wire.exitSelectionMode();
        }
    }
"
class="min-h-screen bg-white">

    <!-- Loading Indicator -->
    <div wire:loading class="fixed top-20 right-4 z-50 bg-primary-600 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2">
        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Loading...</span>
    </div>

    <!-- Top App Bar -->
    <div class="sticky top-0 z-40 bg-white border-b border-outline shadow-sm">
        <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                <!-- Left Section -->
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    @if ($showTrash)
                        <h1 class="flex items-center gap-2 text-xl font-medium text-gray-900 truncate">
                            <span class="material-symbols-outlined text-gray-700">delete</span>
                            <span>Trash</span>
                        </h1>
                    @elseif ($showFavorites)
                        <h1 class="flex items-center gap-2 text-xl font-medium text-gray-900 truncate">
                            <span class="material-symbols-outlined material-symbols-filled text-google-yellow">star</span>
                            <span>Favorites</span>
                        </h1>
                    @elseif ($facesFilter)
                        <h1 class="flex items-center gap-2 text-xl font-medium text-gray-900 truncate">
                            <span class="material-symbols-outlined text-gray-700">face</span>
                            <span>{{ $facesFilterName ?: 'Unknown Person' }}</span>
                        </h1>
                    @elseif ($searchQuery)
                        <h1 class="flex items-center gap-2 text-xl font-medium text-gray-900 truncate">
                            <span class="material-symbols-outlined text-gray-700">search</span>
                            <span>Search results</span>
                        </h1>
                    @else
                        <h1 class="text-xl font-medium text-gray-900">Photos</h1>
                    @endif
                    
                    <span class="text-sm text-gray-500 hidden sm:block">
                        @if ($selectionMode && !empty($selectedIds))
                            {{ count($selectedIds) }} selected
                        @elseif ($searchQuery || $facesFilter)
                            {{ $searchResultsCount ?? count($files) }} {{ Str::plural('result', $searchResultsCount ?? count($files)) }}
                        @else
                            {{ count($files) }} {{ Str::plural('item', count($files)) }}
                        @endif
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1 sm:gap-2">
                    @if ($searchQuery)
                        <button wire:click="clearSearch" 
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200">
                            <span class="material-symbols-outlined text-xl">close</span>
                            <span class="hidden sm:inline">Clear Search</span>
                        </button>
                    @endif

                    @if ($facesFilter)
                        <button wire:click="clearFacesFilter" 
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200">
                            <span class="material-symbols-outlined text-xl">close</span>
                            <span class="hidden sm:inline">Clear Filter</span>
                        </button>
                    @endif

                    @if (!$searchQuery)
                        <select wire:model.live="sortBy" 
                                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 border border-outline rounded-lg transition-colors duration-200 cursor-pointer">
                            <option value="date_taken">Date taken</option>
                            <option value="created_at">Recently added</option>
                            <option value="is_favorite">Favorites first</option>
                        </select>
                    @endif

                    @if (!$showTrash)
                        <button wire:click="{{ $selectionMode ? 'exitSelectionMode' : 'toggleSelectionMode' }}" 
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-full transition-all duration-200
                                       {{ $selectionMode ? 'bg-primary-600 text-white hover:bg-primary-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="material-symbols-outlined text-xl">{{ $selectionMode ? 'close' : 'check_circle' }}</span>
                            <span class="hidden sm:inline">{{ $selectionMode ? 'Cancel' : 'Select' }}</span>
                        </button>
                    @endif

                    <button wire:click="toggleFavorites" 
                            class="p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200"
                            title="{{ $showFavorites ? 'Show all photos' : 'Show favorites' }}">
                        <span class="material-symbols-outlined text-xl {{ $showFavorites ? 'text-google-yellow material-symbols-filled' : '' }}">
                            star
                        </span>
                    </button>

                    <button wire:click="toggleTrash" 
                            class="relative p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200"
                            title="{{ $showTrash ? 'Hide trash' : 'Show trash' }}">
                        <span class="material-symbols-outlined text-xl">{{ $showTrash ? 'folder' : 'delete' }}</span>
                        @if (!$showTrash && $stats['trashed'] > 0)
                            <span class="absolute -top-1 -right-1 bg-google-red text-white text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[20px] text-center">
                                {{ $stats['trashed'] > 99 ? '99+' : $stats['trashed'] }}
                            </span>
                        @endif
                    </button>

                    <a wire:navigate href="{{ route('instant-upload') }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-full shadow-md3-2 hover:shadow-md3-3 transition-all duration-200">
                        <span class="material-symbols-outlined text-xl">upload</span>
                        <span class="hidden sm:inline">Upload</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if (session()->has('message'))
        <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-md3-1 animate-slide-up">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                    <span class="text-sm font-medium text-green-800">{{ session('message') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Actions Bar -->
    @if ($selectionMode)
        <div class="sticky top-16 z-30 bg-primary-50 border-b border-primary-100 shadow-md3-1">
            <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-2">
                        <button wire:click="selectAll" 
                                class="px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 rounded-lg transition-colors duration-200">
                            Select all
                        </button>
                        <button wire:click="deselectAll" 
                                class="px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 rounded-lg transition-colors duration-200">
                            Deselect all
                        </button>
                    </div>

                    @if (!empty($selectedIds))
                        <div class="flex items-center gap-2 flex-wrap">
                            <button wire:click="bulkFavorite" 
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 rounded-lg border border-outline shadow-md3-1 transition-all duration-200">
                                <span class="material-symbols-outlined text-lg">star</span>
                                <span>Favorite</span>
                            </button>

                            <button wire:click="bulkDownload" 
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 rounded-lg border border-outline shadow-md3-1 transition-all duration-200">
                                <span class="material-symbols-outlined text-lg">download</span>
                                <span>Download</span>
                            </button>

                            @if ($showTrash)
                                <button wire:click="bulkRestore" 
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-white hover:bg-green-50 rounded-lg border border-green-500 shadow-md3-1 transition-all duration-200">
                                    <span class="material-symbols-outlined text-lg">restore</span>
                                    <span>Restore</span>
                                </button>

                                <button wire:click="bulkPermanentDelete" 
                                        onclick="return confirm('Permanently delete {{ count($selectedIds) }} items? This cannot be undone!')"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700 bg-white hover:bg-red-50 rounded-lg border border-red-500 shadow-md3-1 transition-all duration-200">
                                    <span class="material-symbols-outlined text-lg">delete_forever</span>
                                    <span>Delete forever</span>
                                </button>
                            @else
                                <button wire:click="bulkTrash" 
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700 bg-white hover:bg-red-50 rounded-lg border border-red-500 shadow-md3-1 transition-all duration-200">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                    <span>Move to trash</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (empty($files))
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-6xl text-gray-400">
                        {{ $showTrash ? 'delete' : ($showFavorites ? 'star' : 'photo_library') }}
                    </span>
                </div>
                <h2 class="text-2xl font-medium text-gray-900 mb-2">
                    @if ($showTrash)
                        No items in trash
                    @elseif ($showFavorites)
                        No favorites yet
                    @elseif ($searchQuery)
                        No results found
                    @else
                        No photos yet
                    @endif
                </h2>
                <p class="text-gray-500 mb-8 max-w-md">
                    @if ($showTrash)
                        Items in your trash will appear here
                    @elseif ($showFavorites)
                        Star your favorite photos to find them here
                    @elseif ($searchQuery)
                        Try a different search term
                    @else
                        Upload your first photos to get started
                    @endif
                </p>
                @if (!$showTrash && !$searchQuery)
                    <a wire:navigate href="{{ route('instant-upload') }}" 
                       class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-full shadow-md3-2 hover:shadow-md3-3 transition-all duration-200">
                        <span class="material-symbols-outlined">upload</span>
                        <span>Upload photos</span>
                    </a>
                @endif
            </div>
        @else
            <!-- Photo Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-1">
                @foreach ($files as $file)
                    <div wire:key="file-{{ $file['id'] }}" 
                         class="relative aspect-square group cursor-pointer overflow-hidden bg-gray-100 hover:outline hover:outline-4 hover:outline-primary-500 transition-all duration-200 animate-fade-in"
                         @if (!$selectionMode)
                            wire:click="viewDetails({{ $file['id'] }})"
                         @else
                            wire:click="toggleSelect({{ $file['id'] }})"
                         @endif>
                        
                        <!-- Image -->
                        <img src="{{ $file['thumbnail_url'] ?? $file['url'] ?? '' }}" 
                             alt="{{ $file['filename'] ?? 'Image' }}"
                             loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">

                        <!-- Selection Overlay -->
                        @if ($selectionMode)
                            <div class="absolute inset-0 bg-black/20 flex items-start justify-end p-2">
                                <div class="w-6 h-6 rounded-full border-2 border-white flex items-center justify-center transition-all duration-200
                                            {{ in_array($file['id'], $selectedIds) ? 'bg-primary-600 scale-110' : 'bg-white/50' }}">
                                    @if (in_array($file['id'], $selectedIds))
                                        <span class="material-symbols-outlined text-white text-sm">check</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
                            <div class="absolute bottom-0 left-0 right-0 p-2">
                                <p class="text-white text-xs font-medium truncate">
                                    {{ $file['filename'] ?? 'Untitled' }}
                                </p>
                                @if (isset($file['date_taken']) && $file['date_taken'])
                                    <p class="text-white/80 text-xs">
                                        {{ \Carbon\Carbon::parse($file['date_taken'])->format('M d, Y') }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Favorite Badge -->
                        @if ($file['is_favorite'])
                            <div class="absolute top-2 left-2 pointer-events-none">
                                <span class="material-symbols-outlined material-symbols-filled text-google-yellow text-xl drop-shadow-lg">
                                    star
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Image Detail Modal -->
    @if (isset($selectedImage) && $selectedImage)
        <div class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center animate-fade-in"
             x-on:click.self="$wire.closeDetails()">
            
            <!-- Close Button -->
            <button wire:click="closeDetails" 
                    class="absolute top-4 right-4 z-10 p-2 text-white hover:bg-white/10 rounded-full transition-colors duration-200">
                <span class="material-symbols-outlined text-3xl">close</span>
            </button>

            <!-- Navigation Buttons -->
            @if (isset($previousFileId) && $previousFileId)
                <button wire:click="viewDetails({{ $previousFileId }})" 
                        class="absolute left-4 top-1/2 -translate-y-1/2 p-3 text-white hover:bg-white/10 rounded-full transition-colors duration-200">
                    <span class="material-symbols-outlined text-4xl">chevron_left</span>
                </button>
            @endif

            @if (isset($nextFileId) && $nextFileId)
                <button wire:click="viewDetails({{ $nextFileId }})" 
                        class="absolute right-4 top-1/2 -translate-y-1/2 p-3 text-white hover:bg-white/10 rounded-full transition-colors duration-200">
                    <span class="material-symbols-outlined text-4xl">chevron_right</span>
                </button>
            @endif

            <!-- Image Container -->
            <div class="max-w-7xl max-h-[90vh] w-full mx-4 flex items-center justify-center animate-scale-in">
                <img src="{{ $selectedImage['url'] ?? '' }}" 
                     alt="{{ $selectedImage['filename'] ?? 'Image' }}"
                     class="max-w-full max-h-[90vh] object-contain">
            </div>

            <!-- Bottom Action Bar -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black via-black/80 to-transparent p-6">
                <div class="max-w-7xl mx-auto">
                    <div class="flex items-center justify-between gap-4 text-white">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-medium truncate">{{ $selectedImage['filename'] ?? 'Untitled' }}</h3>
                            <p class="text-sm text-white/70">
                                {{ isset($selectedImage['date_taken']) && $selectedImage['date_taken'] ? \Carbon\Carbon::parse($selectedImage['date_taken'])->format('F d, Y • g:i A') : 'Date unknown' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if (isset($selectedImage['id']))
                                <button wire:click="toggleFavorite({{ $selectedImage['id'] }})" 
                                        class="p-2 hover:bg-white/10 rounded-full transition-colors duration-200">
                                    <span class="material-symbols-outlined text-2xl {{ isset($selectedImage['is_favorite']) && $selectedImage['is_favorite'] ? 'text-google-yellow material-symbols-filled' : '' }}">
                                        star
                                    </span>
                                </button>

                                <button wire:click="downloadFile({{ $selectedImage['id'] }})" 
                                        class="p-2 hover:bg-white/10 rounded-full transition-colors duration-200">
                                    <span class="material-symbols-outlined text-2xl">download</span>
                                </button>

                                <button wire:click.prevent="editFile({{ $selectedImage['id'] }})" 
                                        class="p-2 hover:bg-white/10 rounded-full transition-colors duration-200"
                                        title="Edit">
                                    <span class="material-symbols-outlined text-2xl">edit</span>
                                </button>

                                <button wire:click.prevent="reanalyze({{ $selectedImage['id'] }})" 
                                        class="p-2 hover:bg-white/10 rounded-full transition-colors duration-200"
                                        title="Re-analyze with AI">
                                    <span class="material-symbols-outlined text-2xl">auto_awesome</span>
                                </button>

                                @if ($showTrash)
                                    <button wire:click="restoreImage({{ $selectedImage['id'] }})" 
                                            class="p-2 hover:bg-white/10 rounded-full transition-colors duration-200">
                                        <span class="material-symbols-outlined text-2xl">restore</span>
                                    </button>
                                @else
                                    <button wire:click="deleteImage({{ $selectedImage['id'] }})" 
                                            class="p-2 hover:bg-white/10 rounded-full transition-colors duration-200">
                                        <span class="material-symbols-outlined text-2xl">delete</span>
                                    </button>
                                @endif
                            @endif

                            <button @click="showImageDetails = !showImageDetails" 
                                    class="p-2 hover:bg-white/10 rounded-full transition-colors duration-200"
                                    title="Toggle Info">
                                <span class="material-symbols-outlined text-2xl">info</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Panel -->
            <div x-show="showImageDetails" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="absolute top-0 right-0 bottom-0 w-full sm:w-96 bg-white overflow-y-auto shadow-md3-5 z-[60]">
                <div class="p-6">
                    <div class="sticky top-0 bg-white pb-4 mb-6 border-b border-gray-200 -mt-6 pt-6 -mx-6 px-6 z-10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-medium text-gray-900">Details</h3>
                            <button @click="showImageDetails = false" 
                                    class="p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- AI Description -->
                        @if (isset($selectedImage['description']) && $selectedImage['description'])
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">auto_awesome</span>
                                    <span>AI Description</span>
                                </h4>
                                <p class="text-sm text-gray-900 leading-relaxed">{{ $selectedImage['description'] }}</p>
                            </div>
                        @endif

                        <!-- Detailed Description -->
                        @if (isset($selectedImage['detailed_description']) && $selectedImage['detailed_description'] && $selectedImage['detailed_description'] !== $selectedImage['description'])
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Detailed Description</h4>
                                <p class="text-sm text-gray-900 leading-relaxed">{{ $selectedImage['detailed_description'] }}</p>
                            </div>
                        @endif

                        <!-- Tags/Labels -->
                        @if (isset($selectedImage['meta_tags']) && !empty($selectedImage['meta_tags']))
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">label</span>
                                    <span>Tags</span>
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($selectedImage['meta_tags'] as $label)
                                        <span wire:click="filterByTag('{{ $label }}')" 
                                              class="px-3 py-1 bg-primary-50 text-primary-700 text-xs rounded-full cursor-pointer hover:bg-primary-100 transition-colors duration-200">
                                            {{ $label }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- File Info -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">description</span>
                                <span>File Info</span>
                            </h4>
                            <dl class="space-y-2 text-sm">
                                @if (isset($selectedImage['filename']))
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">Name</dt>
                                        <dd class="text-gray-900 font-medium text-right break-all">{{ $selectedImage['filename'] }}</dd>
                                    </div>
                                @endif
                                @if (isset($selectedImage['file_size']))
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Size</dt>
                                        <dd class="text-gray-900 font-medium">{{ $selectedImage['file_size'] }}</dd>
                                    </div>
                                @endif
                                @if (isset($selectedImage['dimensions']) && $selectedImage['dimensions'])
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Dimensions</dt>
                                        <dd class="text-gray-900 font-medium">{{ $selectedImage['dimensions'] }}</dd>
                                    </div>
                                @endif
                                @if (isset($selectedImage['mime_type']))
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Type</dt>
                                        <dd class="text-gray-900 font-medium">{{ strtoupper(str_replace('image/', '', $selectedImage['mime_type'])) }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <!-- Camera Info (EXIF) -->
                        @if (isset($selectedImage['camera_make']) || isset($selectedImage['camera_model']) || isset($selectedImage['lens_model']))
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">photo_camera</span>
                                    <span>Camera</span>
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    @if (isset($selectedImage['camera_make']) && isset($selectedImage['camera_model']))
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-gray-500">Camera</dt>
                                            <dd class="text-gray-900 font-medium text-right">{{ $selectedImage['camera_make'] }} {{ $selectedImage['camera_model'] }}</dd>
                                        </div>
                                    @endif
                                    @if (isset($selectedImage['lens_model']))
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-gray-500">Lens</dt>
                                            <dd class="text-gray-900 font-medium text-right">{{ $selectedImage['lens_model'] }}</dd>
                                        </div>
                                    @endif
                                    @if (isset($selectedImage['f_number']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">Aperture</dt>
                                            <dd class="text-gray-900 font-medium">f/{{ $selectedImage['f_number'] }}</dd>
                                        </div>
                                    @endif
                                    @if (isset($selectedImage['exposure_time']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">Exposure</dt>
                                            <dd class="text-gray-900 font-medium">{{ $selectedImage['exposure_time'] }}</dd>
                                        </div>
                                    @endif
                                    @if (isset($selectedImage['iso']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">ISO</dt>
                                            <dd class="text-gray-900 font-medium">{{ $selectedImage['iso'] }}</dd>
                                        </div>
                                    @endif
                                    @if (isset($selectedImage['focal_length']))
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">Focal Length</dt>
                                            <dd class="text-gray-900 font-medium">{{ $selectedImage['focal_length'] }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endif

                        <!-- Date Info -->
                        @if (isset($selectedImage['date_taken']) || isset($selectedImage['created_at']))
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">calendar_today</span>
                                    <span>Dates</span>
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    @if (isset($selectedImage['date_taken']))
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-gray-500">Taken</dt>
                                            <dd class="text-gray-900 font-medium text-right">{{ $selectedImage['date_taken'] }}</dd>
                                        </div>
                                    @endif
                                    @if (isset($selectedImage['created_at']))
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-gray-500">Added</dt>
                                            <dd class="text-gray-900 font-medium text-right">{{ $selectedImage['created_at'] }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endif

                        <!-- GPS Info -->
                        @if (isset($selectedImage['has_gps']) && $selectedImage['has_gps'])
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">location_on</span>
                                    <span>Location</span>
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">Coordinates</dt>
                                        <dd class="text-gray-900 font-medium text-right">
                                            {{ number_format($selectedImage['gps_latitude'], 4) }}, {{ number_format($selectedImage['gps_longitude'], 4) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        @endif

                        <!-- Stats -->
                        @if (isset($selectedImage['view_count']) || isset($selectedImage['face_count']))
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">bar_chart</span>
                                    <span>Statistics</span>
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    @if (isset($selectedImage['view_count']) && $selectedImage['view_count'] > 0)
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">Views</dt>
                                            <dd class="text-gray-900 font-medium">{{ $selectedImage['view_count'] }}</dd>
                                        </div>
                                    @endif
                                    @if (isset($selectedImage['face_count']) && $selectedImage['face_count'] > 0)
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">Faces</dt>
                                            <dd class="text-gray-900 font-medium">{{ $selectedImage['face_count'] }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

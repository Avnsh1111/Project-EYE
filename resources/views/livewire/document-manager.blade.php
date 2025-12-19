<div class="min-h-screen bg-surface-variant">
    <!-- Header Section -->
    <div class="bg-white border-b border-outline">
        <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex-1">
                    <h1 class="text-2xl font-display font-semibold text-gray-900">My Drive</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ count($files) }} items</p>
                </div>

                <!-- View Controls -->
                <div class="flex items-center gap-2">
                    <!-- View Mode Toggle -->
                    <div class="flex items-center bg-gray-100 rounded-lg p-1">
                        <button wire:click="setViewMode('grid')"
                                class="p-2 rounded {{ $viewMode === 'grid' ? 'bg-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }} transition-all duration-200">
                            <span class="material-symbols-outlined text-xl">grid_view</span>
                        </button>
                        <button wire:click="setViewMode('list')"
                                class="p-2 rounded {{ $viewMode === 'list' ? 'bg-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }} transition-all duration-200">
                            <span class="material-symbols-outlined text-xl">view_list</span>
                        </button>
                    </div>

                    <!-- Selection Mode Toggle -->
                    <button wire:click="toggleSelectionMode"
                            class="px-4 py-2 text-sm font-medium {{ $selectionMode ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }} rounded-lg transition-colors duration-200">
                        <span class="material-symbols-outlined text-lg">{{ $selectionMode ? 'close' : 'checklist' }}</span>
                    </button>

                    <!-- Upload Button -->
                    <a href="{{ route('instant-upload') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg shadow-md3-1 hover:shadow-md3-2 transition-all duration-200">
                        <span class="material-symbols-outlined text-lg">upload</span>
                        <span>Upload</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="bg-white border-b border-outline">
        <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-blue-600">description</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Documents</p>
                        <p class="text-sm font-medium text-gray-900">{{ $stats['total_documents'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-red-600">movie</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Videos</p>
                        <p class="text-sm font-medium text-gray-900">{{ $stats['total_videos'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-green-600">audio_file</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Audio</p>
                        <p class="text-sm font-medium text-gray-900">{{ $stats['total_audio'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-purple-600">folder_zip</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Archives</p>
                        <p class="text-sm font-medium text-gray-900">{{ $stats['total_archives'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-gray-600">storage</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Total Size</p>
                        <p class="text-sm font-medium text-gray-900">{{ $stats['total_size'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Sort Bar -->
    <div class="bg-white border-b border-outline">
        <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <input type="text"
                               wire:model.live.debounce.300ms="searchQuery"
                               placeholder="Search files..."
                               class="w-full px-4 py-2 pl-10 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xl">
                            search
                        </span>
                    </div>
                </div>

                <!-- Sort Options -->
                <div class="flex items-center gap-2">
                    <button wire:click="setSortBy('name')"
                            class="px-3 py-2 text-sm {{ $sortBy === 'name' ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }} rounded-lg transition-colors duration-200">
                        Name
                        @if ($sortBy === 'name')
                            <span class="material-symbols-outlined text-sm">{{ $sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                        @endif
                    </button>
                    <button wire:click="setSortBy('date')"
                            class="px-3 py-2 text-sm {{ $sortBy === 'date' ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }} rounded-lg transition-colors duration-200">
                        Date
                        @if ($sortBy === 'date')
                            <span class="material-symbols-outlined text-sm">{{ $sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                        @endif
                    </button>
                    <button wire:click="setSortBy('size')"
                            class="px-3 py-2 text-sm {{ $sortBy === 'size' ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }} rounded-lg transition-colors duration-200">
                        Size
                        @if ($sortBy === 'size')
                            <span class="material-symbols-outlined text-sm">{{ $sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Selection Actions -->
    @if ($selectionMode && !empty($selectedIds))
        <div class="bg-primary-600 text-white">
            <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-medium">{{ count($selectedIds) }} selected</span>
                        <button wire:click="selectAll" class="text-sm hover:underline">Select All</button>
                        <button wire:click="deselectAll" class="text-sm hover:underline">Deselect All</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="bulkDelete" 
                                wire:confirm="Are you sure you want to delete selected files?"
                                class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition-colors duration-200">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (count($files) > 0)
            <!-- Grid View -->
            @if ($viewMode === 'grid')
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-4">
                    @foreach ($files as $file)
                        <div class="group relative bg-white rounded-xl border border-outline hover:shadow-md3-2 transition-all duration-200 animate-fade-in">
                            <!-- Selection Checkbox -->
                            @if ($selectionMode)
                                <div class="absolute top-2 left-2 z-10">
                                    <input type="checkbox"
                                           wire:click="toggleSelection({{ $file['id'] }})"
                                           {{ in_array($file['id'], $selectedIds) ? 'checked' : '' }}
                                           class="w-5 h-5 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                                </div>
                            @endif

                            <!-- File Preview -->
                            <button wire:click="selectFile({{ $file['id'] }})" 
                                    class="w-full aspect-square bg-gray-50 flex items-center justify-center rounded-t-xl overflow-hidden hover:bg-gray-100 transition-colors duration-200">
                                @if (str_starts_with($file['mime_type'] ?? '', 'video/'))
                                    <div class="relative w-full h-full">
                                        @if ($file['thumbnail_url'])
                                            <img src="{{ $file['thumbnail_url'] }}" alt="{{ $file['name'] }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-red-50">
                                                <span class="material-symbols-outlined text-6xl text-red-600">movie</span>
                                            </div>
                                        @endif
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="w-12 h-12 bg-black/60 backdrop-blur-sm rounded-full flex items-center justify-center">
                                                <span class="material-symbols-outlined text-white text-2xl">play_arrow</span>
                                            </div>
                                        </div>
                                    </div>
                                @elseif (str_starts_with($file['mime_type'] ?? '', 'audio/'))
                                    <div class="w-full h-full flex items-center justify-center bg-green-50">
                                        <span class="material-symbols-outlined text-6xl text-green-600">audio_file</span>
                                    </div>
                                @elseif ($file['type'] === 'document')
                                    <div class="w-full h-full flex items-center justify-center bg-blue-50">
                                        <span class="material-symbols-outlined text-6xl text-blue-600">description</span>
                                    </div>
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-50">
                                        <span class="text-6xl">{{ $this->getFileTypeIcon($file['type']) }}</span>
                                    </div>
                                @endif
                            </button>

                            <!-- File Info -->
                            <div class="p-3">
                                <p class="text-sm font-medium text-gray-900 truncate" title="{{ $file['name'] }}">
                                    {{ $file['name'] }}
                                </p>
                                <div class="flex items-center justify-between mt-1">
                                    <p class="text-xs text-gray-500">{{ $file['size_human'] }}</p>
                                    <button wire:click="toggleFavorite({{ $file['id'] }})" 
                                            class="p-1 hover:bg-gray-100 rounded transition-colors duration-200">
                                        <span class="material-symbols-outlined text-sm {{ $file['is_favorite'] ? 'text-yellow-500' : 'text-gray-400' }}">
                                            {{ $file['is_favorite'] ? 'star' : 'star_outline' }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- List View -->
                <div class="bg-white rounded-xl border border-outline overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-outline">
                            <tr>
                                @if ($selectionMode)
                                    <th class="w-12 px-4 py-3">
                                        <input type="checkbox" 
                                               wire:click="selectAll"
                                               class="w-5 h-5 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                                    </th>
                                @endif
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modified</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline">
                            @foreach ($files as $file)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    @if ($selectionMode)
                                        <td class="px-4 py-4">
                                            <input type="checkbox"
                                                   wire:click="toggleSelection({{ $file['id'] }})"
                                                   {{ in_array($file['id'], $selectedIds) ? 'checked' : '' }}
                                                   class="w-5 h-5 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                                        </td>
                                    @endif
                                    <td class="px-6 py-4">
                                        <button wire:click="selectFile({{ $file['id'] }})" 
                                                class="flex items-center gap-3 text-left hover:text-primary-600 transition-colors duration-200">
                                            <span class="material-symbols-outlined text-2xl text-gray-400">
                                                {{ $this->getFolderIcon($file['type']) }}
                                            </span>
                                            <span class="text-sm font-medium text-gray-900">{{ $file['name'] }}</span>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 capitalize">{{ $file['type'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $file['size_human'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $file['updated_at']->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button wire:click="toggleFavorite({{ $file['id'] }})" 
                                                    class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                <span class="material-symbols-outlined text-lg {{ $file['is_favorite'] ? 'text-yellow-500' : 'text-gray-400' }}">
                                                    {{ $file['is_favorite'] ? 'star' : 'star_outline' }}
                                                </span>
                                            </button>
                                            <a href="{{ $file['file_url'] }}" 
                                               download="{{ $file['name'] }}"
                                               class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                                <span class="material-symbols-outlined text-lg text-gray-600">download</span>
                                            </a>
                                            <button wire:click="deleteFile({{ $file['id'] }})" 
                                                    wire:confirm="Are you sure you want to delete this file?"
                                                    class="p-2 hover:bg-red-50 rounded-lg transition-colors duration-200">
                                                <span class="material-symbols-outlined text-lg text-red-600">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-6xl text-gray-400">folder_open</span>
                </div>
                <h2 class="text-2xl font-medium text-gray-900 mb-2">No files yet</h2>
                <p class="text-gray-500 mb-8 max-w-md">
                    Upload documents, videos, audio files, and more to get started
                </p>
                <a href="{{ route('instant-upload') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md3-1 hover:shadow-md3-2 transition-all duration-200">
                    <span class="material-symbols-outlined">upload</span>
                    <span>Upload Files</span>
                </a>
            </div>
        @endif
    </div>

    <!-- File Details Side Panel -->
    @if ($selectedFile)
        <div class="fixed inset-0 z-50 flex items-center justify-end bg-black/50 animate-fade-in"
             wire:click.self="closeDetails">
            <div class="w-full max-w-md h-full bg-white shadow-md3-3 overflow-y-auto animate-slide-in-right">
                <!-- Header -->
                <div class="sticky top-0 bg-white border-b border-outline px-6 py-4 flex items-center justify-between z-10">
                    <h2 class="text-lg font-medium text-gray-900">File Details</h2>
                    <button wire:click="closeDetails" 
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                        <span class="material-symbols-outlined text-gray-600">close</span>
                    </button>
                </div>

                <!-- Preview -->
                <div class="p-6 border-b border-outline">
                    <div class="aspect-video bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden">
                        @if (str_starts_with($selectedFile['mime_type'] ?? '', 'video/'))
                            <video src="{{ $selectedFile['file_url'] }}" controls class="w-full h-full">
                                Your browser does not support the video tag.
                            </video>
                        @elseif (str_starts_with($selectedFile['mime_type'] ?? '', 'audio/'))
                            <audio src="{{ $selectedFile['file_url'] }}" controls class="w-full">
                                Your browser does not support the audio tag.
                            </audio>
                        @else
                            <span class="text-6xl">{{ $this->getFileTypeIcon($selectedFile['type']) }}</span>
                        @endif
                    </div>
                </div>

                <!-- File Info -->
                <div class="p-6 space-y-4">
                    <div>
                        <h3 class="font-medium text-gray-900 mb-1">{{ $selectedFile['name'] }}</h3>
                        <p class="text-sm text-gray-500 capitalize">{{ $selectedFile['type'] }} • {{ $selectedFile['size_human'] }}</p>
                    </div>

                    @if ($selectedFile['description'])
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-1">Description</h4>
                            <p class="text-sm text-gray-600">{{ $selectedFile['description'] }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-outline">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Created</p>
                            <p class="text-sm font-medium text-gray-900">{{ $selectedFile['created_at']->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Modified</p>
                            <p class="text-sm font-medium text-gray-900">{{ $selectedFile['updated_at']->format('M d, Y') }}</p>
                        </div>
                    </div>

                    @if ($selectedFile['duration'])
                        <div class="pt-4 border-t border-outline">
                            <p class="text-xs text-gray-500 mb-1">Duration</p>
                            <p class="text-sm font-medium text-gray-900">{{ gmdate('H:i:s', $selectedFile['duration']) }}</p>
                        </div>
                    @endif

                    @if ($selectedFile['page_count'])
                        <div class="pt-4 border-t border-outline">
                            <p class="text-xs text-gray-500 mb-1">Pages</p>
                            <p class="text-sm font-medium text-gray-900">{{ $selectedFile['page_count'] }}</p>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-2 pt-4 border-t border-outline">
                        <a href="{{ $selectedFile['file_url'] }}" 
                           download="{{ $selectedFile['name'] }}"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <span class="material-symbols-outlined text-lg">download</span>
                            <span>Download</span>
                        </a>
                        <button wire:click="deleteFile({{ $selectedFile['id'] }})" 
                                wire:confirm="Are you sure you want to delete this file?"
                                class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium rounded-lg transition-colors duration-200">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 3000)"
             class="fixed bottom-6 right-6 bg-green-600 text-white px-6 py-3 rounded-lg shadow-md3-3 animate-fade-in z-50">
            {{ session('message') }}
        </div>
    @endif
</div>


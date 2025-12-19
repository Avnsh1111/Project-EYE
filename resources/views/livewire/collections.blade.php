<div class="min-h-screen bg-surface-variant">
    <!-- Top App Bar -->
    <div class="sticky top-0 z-50 bg-white border-b border-outline shadow-sm">
        <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-medium text-gray-900">Collections</h1>
                    <span class="text-sm text-gray-500">
                        {{ $stats['total_images'] }} {{ Str::plural('item', $stats['total_images']) }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button class="p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200" title="Grid view">
                        <span class="material-symbols-outlined text-xl">grid_view</span>
                    </button>
                    <button class="p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200" title="List view">
                        <span class="material-symbols-outlined text-xl">view_list</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <!-- Total Files -->
            <div class="bg-white rounded-xl shadow-md3-1 p-6 hover:shadow-md3-2 transition-shadow duration-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl text-primary-600">folder</span>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_images']) }}</p>
                        <p class="text-sm text-gray-500">Total files</p>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="bg-white rounded-xl shadow-md3-1 p-6 hover:shadow-md3-2 transition-shadow duration-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-google-blue/10 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl text-google-blue">label</span>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900">{{ $stats['total_categories'] }}</p>
                        <p class="text-sm text-gray-500">Categories</p>
                    </div>
                </div>
            </div>

            <!-- People & Faces -->
            <div class="bg-white rounded-xl shadow-md3-1 p-6 hover:shadow-md3-2 transition-shadow duration-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-google-green/10 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl text-google-green">face</span>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900">{{ $stats['total_faces'] }}</p>
                        <p class="text-sm text-gray-500">With faces</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Photo Folders (Organized by Date) -->
        @if (!empty($photoFolders))
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-medium text-gray-900 flex items-center gap-3">
                        <span class="material-symbols-outlined text-3xl text-gray-700">photo_library</span>
                        <span>Photos by Date</span>
                    </h2>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-4">
                    @foreach ($photoFolders as $folder)
                        <a wire:navigate href="{{ route('gallery') }}?date={{ $folder['year'] }}-{{ str_pad(array_search($folder['month'], ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']) + 1, 2, '0', STR_PAD_LEFT) }}" 
                           class="group bg-white rounded-2xl shadow-md3-1 hover:shadow-md3-3 overflow-hidden transition-all duration-200 hover:-translate-y-1 animate-fade-in">
                            
                            <!-- Image Mosaic -->
                            <div class="relative aspect-square bg-gray-100 overflow-hidden">
                                @if (isset($folder['images']) && count($folder['images']) === 1)
                                    <img src="{{ $folder['images'][0]['url'] ?? '' }}" 
                                         alt="{{ $folder['name'] ?? 'Folder' }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @elseif (isset($folder['images']) && count($folder['images']) > 0)
                                    <div class="grid grid-cols-2 gap-0.5 h-full">
                                        @foreach (array_slice($folder['images'], 0, 4) as $image)
                                            <div class="bg-gray-200 overflow-hidden">
                                                <img src="{{ $image['url'] ?? '' }}" 
                                                     alt="{{ $folder['name'] ?? 'Folder' }}"
                                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                            </div>
                                        @endforeach
                                        @for ($i = count($folder['images']); $i < 4; $i++)
                                            <div class="bg-gray-100"></div>
                                        @endfor
                                    </div>
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-100 to-primary-200">
                                        <span class="material-symbols-outlined text-6xl text-primary-600">folder</span>
                                    </div>
                                @endif

                                <!-- Photo Count Badge -->
                                <div class="absolute bottom-2 right-2 px-2.5 py-1 bg-black/70 backdrop-blur-sm text-white text-xs font-medium rounded-full">
                                    {{ $folder['count'] ?? 0 }}
                                </div>
                            </div>

                            <!-- Folder Info -->
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 text-center truncate">
                                    {{ $folder['name'] ?? 'Unknown' }}
                                </h3>
                                <p class="text-xs text-gray-500 text-center mt-1">
                                    {{ $folder['count'] ?? 0 }} {{ Str::plural('photo', $folder['count'] ?? 0) }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- People Collections -->
        @if (!empty($faceCollections))
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-medium text-gray-900 flex items-center gap-3">
                        <span class="material-symbols-outlined text-3xl text-gray-700">face</span>
                        <span>People</span>
                    </h2>
                    <a wire:navigate href="{{ route('people-and-pets') }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50 rounded-lg transition-colors duration-200">
                        <span>View all</span>
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    @foreach ($faceCollections as $collection)
                        <a wire:navigate href="{{ route('people-and-pets') }}" 
                           class="group bg-white rounded-2xl shadow-md3-1 hover:shadow-md3-3 overflow-hidden transition-all duration-200 hover:-translate-y-1 animate-fade-in">
                            
                            <!-- Image Mosaic -->
                            <div class="relative aspect-square bg-gray-100 overflow-hidden">
                                @if (isset($collection['images']) && count($collection['images']) === 1)
                                    <img src="{{ $collection['images'][0]['url'] ?? '' }}" 
                                         alt="{{ $collection['name'] ?? 'Collection' }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @elseif (isset($collection['images']) && count($collection['images']) > 0)
                                    <div class="grid grid-cols-2 gap-0.5 h-full">
                                        @foreach (array_slice($collection['images'], 0, 4) as $image)
                                            <div class="bg-gray-200 overflow-hidden">
                                                <img src="{{ $image['url'] ?? '' }}" 
                                                     alt="{{ $collection['name'] ?? 'Collection' }}"
                                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                            </div>
                                        @endforeach
                                        @for ($i = count($collection['images']); $i < 4; $i++)
                                            <div class="bg-gray-100"></div>
                                        @endfor
                                    </div>
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-100 to-primary-200">
                                        <span class="text-6xl">{{ $collection['icon'] ?? '📁' }}</span>
                                    </div>
                                @endif

                                <!-- Overlay Count -->
                                @if (isset($collection['count']) && $collection['count'] > 4)
                                    <div class="absolute top-2 right-2 px-2 py-1 bg-black/60 backdrop-blur-sm text-white text-xs font-medium rounded-full">
                                        +{{ $collection['count'] - 4 }}
                                    </div>
                                @endif
                            </div>

                            <!-- Info -->
                            <div class="p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xl">{{ $collection['icon'] ?? '📁' }}</span>
                                    <h3 class="font-medium text-gray-900 truncate">{{ $collection['name'] ?? 'Untitled' }}</h3>
                                </div>
                                <p class="text-sm text-gray-500">
                                    {{ $collection['count'] ?? 0 }} {{ Str::plural('photo', $collection['count'] ?? 0) }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Category Collections -->
        @if (!empty($collections))
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-medium text-gray-900 flex items-center gap-3">
                        <span class="material-symbols-outlined text-3xl text-gray-700">category</span>
                        <span>Categories</span>
                    </h2>
                </div>

                <!-- Category Grid - Google Drive Style -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach ($collections as $collection)
                        <a wire:navigate href="{{ route('gallery') }}?q={{ urlencode($collection['slug']) }}"
                           class="group bg-white rounded-xl shadow-md3-1 hover:shadow-md3-3 overflow-hidden transition-all duration-200 hover:-translate-y-1 animate-fade-in">
                            
                            <!-- Preview Row -->
                            <div class="relative h-40 bg-gradient-to-br {{ $collection['gradient'] ?? 'from-primary-100 to-primary-200' }} overflow-hidden">
                                @if (isset($collection['images']) && !empty($collection['images']))
                                    <div class="absolute inset-0 flex gap-1 p-2">
                                        @foreach (array_slice($collection['images'], 0, 3) as $index => $image)
                                            <div class="flex-1 bg-white rounded-lg shadow-md3-2 overflow-hidden transform rotate-{{ $index * 2 - 2 }} group-hover:rotate-0 transition-transform duration-300">
                                                <img src="{{ $image['url'] ?? '' }}" 
                                                     alt="{{ $collection['name'] ?? 'Collection' }}"
                                                     class="w-full h-full object-cover">
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-6xl opacity-20">{{ $collection['icon'] ?? '📁' }}</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Info -->
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <span class="text-2xl flex-shrink-0">{{ $collection['icon'] ?? '📁' }}</span>
                                        <h3 class="font-medium text-gray-900 truncate">{{ $collection['name'] ?? 'Untitled' }}</h3>
                                    </div>
                                    <button class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors duration-200 flex-shrink-0"
                                            onclick="event.preventDefault(); event.stopPropagation();">
                                        <span class="material-symbols-outlined text-lg">more_vert</span>
                                    </button>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">
                                        {{ $collection['count'] ?? 0 }} {{ Str::plural('item', $collection['count'] ?? 0) }}
                                    </span>
                                    <span class="text-gray-400 text-xs">
                                        {{ isset($collection['updated_at']) && $collection['updated_at'] ? \Carbon\Carbon::parse($collection['updated_at'])->diffForHumans() : 'Recently' }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Empty State -->
        @if (empty($collections) && empty($faceCollections))
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-6xl text-gray-400">folder_open</span>
                </div>
                <h2 class="text-2xl font-medium text-gray-900 mb-2">No collections yet</h2>
                <p class="text-gray-500 mb-8 max-w-md">
                    Upload photos to automatically organize them into collections based on AI analysis
                </p>
                <a wire:navigate href="{{ route('instant-upload') }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-full shadow-md3-2 hover:shadow-md3-3 transition-all duration-200">
                    <span class="material-symbols-outlined">upload</span>
                    <span>Upload photos</span>
                </a>
            </div>
        @endif
    </div>
</div>

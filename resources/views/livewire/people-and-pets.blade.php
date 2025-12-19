<div class="min-h-screen bg-surface-variant">
    <!-- Top App Bar -->
    <div class="sticky top-0 z-50 bg-white border-b border-outline shadow-sm">
        <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-medium text-gray-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-2xl text-gray-700">face</span>
                        <span>People & Pets</span>
                    </h1>
                    <span class="text-sm text-gray-500">
                        {{ $stats['total_faces'] }} {{ Str::plural('person', $stats['total_faces']) }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button class="p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200" title="Grid view">
                        <span class="material-symbols-outlined text-xl">grid_view</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (!empty($faceGroups))
            <!-- People Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-4">
                @foreach ($faceGroups as $group)
                    <a wire:navigate href="{{ route('gallery') }}?faces={{ $group['id'] }}" 
                       class="group bg-white rounded-2xl shadow-md3-1 hover:shadow-md3-3 overflow-hidden transition-all duration-200 hover:-translate-y-1 animate-fade-in">
                        
                        <!-- Face Preview -->
                        <div class="aspect-square bg-gray-100 relative overflow-hidden">
                            @if (!empty($group['sample_image']))
                                <img src="{{ $group['sample_image'] }}" 
                                     alt="{{ $group['name'] }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-100 to-primary-200">
                                    <span class="text-6xl">{{ $group['icon'] ?? '👤' }}</span>
                                </div>
                            @endif

                            <!-- Photo Count Badge -->
                            <div class="absolute bottom-2 right-2 px-2.5 py-1 bg-black/70 backdrop-blur-sm text-white text-xs font-medium rounded-full">
                                {{ $group['count'] }} {{ Str::plural('photo', $group['count']) }}
                            </div>
                        </div>

                        <!-- Name -->
                        <div class="p-4">
                            <h3 class="font-medium text-gray-900 text-center truncate">
                                {{ $group['name'] }}
                            </h3>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-6xl text-gray-400">face</span>
                </div>
                <h2 class="text-2xl font-medium text-gray-900 mb-2">No faces detected yet</h2>
                <p class="text-gray-500 mb-8 max-w-md">
                    Upload photos with people to automatically detect and group faces
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

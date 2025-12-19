<div wire:poll.5s="loadStatus" class="min-h-screen bg-surface-variant">
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <div class="flex items-start justify-between mb-8 flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-display font-bold text-gray-900 flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center shadow-md3-2">
                        <span class="material-symbols-outlined text-white text-2xl">pending_actions</span>
                    </div>
                    <span>Background Processing</span>
                </h1>
                <p class="text-base text-gray-600">
                    Auto-refreshes every 5 seconds • Click Refresh for instant update
                </p>
            </div>

            <button wire:click="loadStatus" 
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg border-2 border-outline hover:border-gray-300 transition-all duration-200 shadow-md3-1 hover:shadow-md3-2">
                <span class="material-symbols-outlined text-xl">refresh</span>
                <span>Refresh</span>
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <!-- Pending -->
            <div wire:click="toggleSection('pending')"
                 class="bg-white rounded-2xl p-6 text-center cursor-pointer transition-all duration-200 hover:-translate-y-1 hover:shadow-md3-3 
                        {{ $showPending ? 'ring-2 ring-yellow-500 shadow-md3-3 bg-yellow-50' : 'shadow-md3-1' }}">
                <div class="text-4xl font-bold text-yellow-600 mb-2">
                    {{ $stats['pending'] }}
                </div>
                <div class="text-sm {{ $showPending ? 'text-yellow-700 font-semibold' : 'text-gray-600' }}">
                    <span class="material-symbols-outlined text-base align-middle">hourglass_empty</span>
                    Pending {{ $showPending ? '▼' : '▶' }}
                </div>
            </div>

            <!-- Processing -->
            <div wire:click="toggleSection('processing')"
                 class="bg-white rounded-2xl p-6 text-center cursor-pointer transition-all duration-200 hover:-translate-y-1 hover:shadow-md3-3
                        {{ $showProcessing ? 'ring-2 ring-primary-500 shadow-md3-3 bg-primary-50' : 'shadow-md3-1' }}">
                <div class="text-4xl font-bold text-primary-600 mb-2">
                    {{ $stats['processing'] }}
                </div>
                <div class="text-sm {{ $showProcessing ? 'text-primary-700 font-semibold' : 'text-gray-600' }}">
                    <span class="material-symbols-outlined text-base align-middle animate-spin">settings</span>
                    Processing {{ $showProcessing ? '▼' : '▶' }}
                </div>
            </div>

            <!-- Completed -->
            <div wire:click="toggleSection('completed')"
                 class="bg-white rounded-2xl p-6 text-center cursor-pointer transition-all duration-200 hover:-translate-y-1 hover:shadow-md3-3
                        {{ $showCompleted ? 'ring-2 ring-green-600 shadow-md3-3 bg-green-50' : 'shadow-md3-1' }}">
                <div class="text-4xl font-bold text-green-600 mb-2">
                    {{ $stats['completed'] }}
                </div>
                <div class="text-sm {{ $showCompleted ? 'text-green-700 font-semibold' : 'text-gray-600' }}">
                    <span class="material-symbols-outlined material-symbols-filled text-base align-middle">check_circle</span>
                    Completed {{ $showCompleted ? '▼' : '▶' }}
                </div>
            </div>

            <!-- Failed -->
            <div wire:click="toggleSection('failed')"
                 class="bg-white rounded-2xl p-6 text-center cursor-pointer transition-all duration-200 hover:-translate-y-1 hover:shadow-md3-3
                        {{ $showFailed ? 'ring-2 ring-red-500 shadow-md3-3 bg-red-50' : 'shadow-md3-1' }}">
                <div class="text-4xl font-bold text-red-600 mb-2">
                    {{ $stats['failed'] }}
                </div>
                <div class="text-sm {{ $showFailed ? 'text-red-700 font-semibold' : 'text-gray-600' }}">
                    <span class="material-symbols-outlined material-symbols-filled text-base align-middle">error</span>
                    Failed {{ $showFailed ? '▼' : '▶' }}
                </div>
            </div>
        </div>

        <!-- Pending Files -->
        @if (!empty($pending_files) && $showPending)
            <div class="bg-white rounded-2xl shadow-md3-2 p-6 mb-6 animate-slide-down" x-data x-show="true" x-transition>
                <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-6">
                    <span class="material-symbols-outlined text-yellow-600">hourglass_empty</span>
                    <span>Pending ({{ count($pending_files) }})</span>
                </h2>

                @if (session()->has('message'))
                    <div class="p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg mb-6">
                        <span class="text-green-700">✓ {{ session('message') }}</span>
                    </div>
                @endif

                <div class="space-y-4">
                    @foreach ($pending_files as $file)
                        <div class="flex items-center gap-4 p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors duration-200">
                            <!-- Thumbnail -->
                            <img src="{{ $file['url'] }}" 
                                 alt="{{ $file['filename'] }}" 
                                 class="w-20 h-20 object-cover rounded-lg shadow-md3-1">

                            <!-- File Info -->
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 truncate mb-1">
                                    {{ $file['filename'] }}
                                </div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-block bg-yellow-500 text-white px-2 py-0.5 rounded text-xs font-medium uppercase">
                                        {{ $file['media_type'] ?? 'Unknown' }}
                                    </span>
                                    <span class="text-sm text-gray-600">
                                        {{ $file['file_size'] }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button wire:click="downloadFile({{ $file['id'] }})" 
                                        class="p-2 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors duration-200"
                                        title="Download file">
                                    <span class="material-symbols-outlined">download</span>
                                </button>
                                <button wire:click="reanalyze({{ $file['id'] }})" 
                                        class="p-2 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors duration-200"
                                        title="Process now">
                                    <span class="material-symbols-outlined">play_arrow</span>
                                </button>
                                <button wire:click="cancelPending({{ $file['id'] }})" 
                                        class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                        title="Cancel and remove"
                                        onclick="return confirm('Are you sure you want to cancel and delete this file?')">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Processing Files -->
        @if (!empty($processing_files) && $showProcessing)
            <div class="bg-white rounded-2xl shadow-md3-2 p-6 mb-6 animate-slide-down" x-data x-show="true" x-transition>
                <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-6">
                    <span class="material-symbols-outlined text-primary-600 animate-spin">settings</span>
                    <span>Processing ({{ count($processing_files) }})</span>
                </h2>

                <div class="space-y-4">
                    @foreach ($processing_files as $img)
                        <div class="flex items-center gap-4 p-4 bg-primary-50 rounded-xl border-l-4 border-primary-500">
                            <img src="{{ $img['url'] }}" 
                                 alt="{{ $img['filename'] }}" 
                                 class="w-20 h-20 object-cover rounded-lg shadow-md3-1">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 mb-1">{{ $img['filename'] }}</div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 border-2 border-primary-600 border-t-transparent rounded-full animate-spin"></div>
                                    <span class="text-sm text-primary-700 font-medium">Processing...</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Completed Files -->
        @if (!empty($completed_files) && $showCompleted)
            <div class="bg-white rounded-2xl shadow-md3-2 p-6 mb-6 animate-slide-down" x-data x-show="true" x-transition>
                <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-6">
                    <span class="material-symbols-outlined material-symbols-filled text-green-600">check_circle</span>
                    <span>Completed ({{ count($completed_files) }})</span>
                </h2>

                <div class="space-y-4">
                    @foreach ($completed_files as $file)
                        <div class="flex items-center gap-4 p-4 bg-green-50 rounded-xl border-l-4 border-green-500">
                            <img src="{{ $file['url'] }}" 
                                 alt="{{ $file['filename'] }}" 
                                 class="w-20 h-20 object-cover rounded-lg shadow-md3-1">

                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 truncate mb-1">
                                    {{ $file['filename'] }}
                                </div>
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <span class="inline-block bg-green-600 text-white px-2 py-0.5 rounded text-xs font-medium uppercase">
                                        Completed
                                    </span>
                                    <span class="text-sm text-gray-600">
                                        ✓ Completed {{ $file['completed_at'] }}
                                    </span>
                                </div>
                                @if (!empty($file['description']))
                                    <p class="text-sm text-gray-600 line-clamp-2">
                                        {{ $file['description'] }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <button wire:click="downloadFile({{ $file['id'] }})" 
                                        class="p-2 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors duration-200"
                                        title="Download file">
                                    <span class="material-symbols-outlined">download</span>
                                </button>
                                <button wire:click="reanalyze({{ $file['id'] }})" 
                                        class="p-2 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors duration-200"
                                        title="Reanalyze">
                                    <span class="material-symbols-outlined">refresh</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Failed Files -->
        @if (!empty($failed_files) && $showFailed)
            <div class="bg-white rounded-2xl shadow-md3-2 p-6 mb-6 animate-slide-down" x-data x-show="true" x-transition>
                <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-6">
                    <span class="material-symbols-outlined material-symbols-filled text-red-600">error</span>
                    <span>Failed ({{ count($failed_files) }})</span>
                </h2>

                <div class="space-y-4">
                    @foreach ($failed_files as $file)
                        <div class="flex items-center gap-4 p-4 bg-red-50 rounded-xl border-l-4 border-red-500">
                            <img src="{{ $file['url'] }}" 
                                 alt="{{ $file['filename'] }}" 
                                 class="w-20 h-20 object-cover rounded-lg shadow-md3-1">

                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 truncate mb-1">
                                    {{ $file['filename'] }}
                                </div>
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <span class="inline-block bg-red-600 text-white px-2 py-0.5 rounded text-xs font-medium uppercase">
                                        Failed
                                    </span>
                                </div>
                                <div class="text-sm text-red-600">
                                    ⚠️ {{ $file['error'] ?? 'Processing failed' }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button wire:click="downloadFile({{ $file['id'] }})" 
                                        class="p-2 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors duration-200"
                                        title="Download file">
                                    <span class="material-symbols-outlined">download</span>
                                </button>
                                <button wire:click="retryFailed({{ $file['id'] }})" 
                                        class="p-2 text-red-600 hover:text-red-700 hover:bg-red-100 rounded-lg transition-colors duration-200"
                                        title="Retry processing">
                                    <span class="material-symbols-outlined">refresh</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Quick Actions -->
        <div class="flex justify-center gap-4">
            <a wire:navigate href="{{ route('gallery') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-white hover:bg-gray-50 text-primary-600 font-medium rounded-lg border-2 border-primary-200 hover:border-primary-300 transition-all duration-200 shadow-md3-1 hover:shadow-md3-2">
                <span class="material-symbols-outlined">photo_library</span>
                <span>View Gallery</span>
            </a>
            <a wire:navigate href="{{ route('instant-upload') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md3-2 hover:shadow-md3-3 transition-all duration-200">
                <span class="material-symbols-outlined">cloud_upload</span>
                <span>Upload More</span>
            </a>
        </div>
    </div>
</div>

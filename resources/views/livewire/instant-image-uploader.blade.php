<div wire:poll.2s="refreshProcessingStatus" 
     x-data="uploadManager()" 
     @keydown.escape="clearSelection" 
     @paste.window="handlePaste($event)"
     class="min-h-screen bg-surface-variant p-4 sm:p-6 lg:p-8">

    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-start justify-between mb-8 flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-display font-bold text-gray-900 flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center shadow-md3-2">
                        <span class="material-symbols-outlined text-white text-2xl">cloud_upload</span>
                    </div>
                    <span>Instant Upload</span>
                </h1>
                <p class="text-base text-gray-600 ml-15">
                    Upload files instantly with AI-powered analysis happening in the background
                </p>
            </div>

            @if (!empty($uploaded_files))
                <div class="flex gap-3">
                    <div class="px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-center min-w-[100px]">
                        <div class="text-xs font-medium text-green-700 mb-1">Completed</div>
                        <div class="text-2xl font-bold text-green-700">{{ $upload_statistics['success_count'] }}</div>
                    </div>
                    @if ($upload_statistics['failed_count'] > 0)
                        <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-center min-w-[100px]">
                            <div class="text-xs font-medium text-red-700 mb-1">Failed</div>
                            <div class="text-2xl font-bold text-red-700">{{ $upload_statistics['failed_count'] }}</div>
                        </div>
                    @endif
                    <div class="px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl text-center min-w-[100px]">
                        <div class="text-xs font-medium text-blue-700 mb-1">Total Size</div>
                        <div class="text-lg font-bold text-blue-700">{{ number_format($upload_statistics['total_size'] / 1048576, 1) }} MB</div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-md3-1 mb-6 animate-slide-up">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-red-600">error</span>
                    <div class="flex-1">
                        <strong class="text-red-800 font-medium">Validation errors</strong>
                        <ul class="mt-2 ml-5 list-disc text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Upload Card -->
        <div class="bg-white rounded-2xl shadow-md3-2 overflow-hidden animate-fade-in">
            <form wire:submit.prevent="uploadInstantly">
                <!-- Drag and Drop Area -->
                <label
                    for="files"
                    class="block min-h-[320px] flex flex-col items-center justify-center cursor-pointer border-b-2 border-dashed border-outline hover:border-primary-500 hover:bg-primary-50/30 transition-all duration-200 p-8"
                    @dragover.prevent="$el.classList.add('!border-primary-500', '!bg-primary-50')"
                    @dragleave.prevent="$el.classList.remove('!border-primary-500', '!bg-primary-50')"
                    @drop.prevent="$el.classList.remove('!border-primary-500', '!bg-primary-50')"
                >
                    <!-- Loading State -->
                    <div wire:loading wire:target="files" class="text-center">
                        <div class="w-16 h-16 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4"></div>
                        <p class="text-gray-600 font-medium">Loading files...</p>
                    </div>

                    <!-- Upload Content -->
                    <div wire:loading.remove wire:target="files" class="text-center">
                        @if (!empty($files))
                            <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-700 rounded-full flex items-center justify-center mx-auto mb-4 shadow-md3-3 animate-scale-in">
                                <span class="material-symbols-outlined text-white text-5xl">check</span>
                            </div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-2">
                                {{ count($files) }} {{ Str::plural('file', count($files)) }} ready
                            </h3>
                            <p class="text-gray-600">Click "Upload Instantly" to start processing</p>
                        @else
                            <div class="w-20 h-20 bg-gradient-to-br from-primary-500 to-primary-700 rounded-full flex items-center justify-center mx-auto mb-4 shadow-md3-3">
                                <span class="material-symbols-outlined text-white text-5xl">cloud_upload</span>
                            </div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-2">
                                Drop files here or click to browse
                            </h3>
                            <p class="text-gray-600 mb-6">
                                Supports images, videos, documents, audio • Up to 500MB per file
                            </p>
                            <div class="flex items-center justify-center gap-6 flex-wrap text-gray-500 text-sm">
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-xl">image</span>
                                    <span>Images</span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-xl">videocam</span>
                                    <span>Videos</span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-xl">description</span>
                                    <span>Documents</span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-xl">audiotrack</span>
                                    <span>Audio</span>
                                </span>
                            </div>
                        @endif
                    </div>

                    <input
                        type="file"
                        id="files"
                        wire:model="files"
                        multiple
                        accept="image/*,video/*,.pdf,.doc,.docx,.txt,.mp3,.wav"
                        class="hidden"
                    >
                </label>

                <!-- Action Buttons -->
                @if (!empty($files))
                    <div class="p-6 bg-surface-variant flex items-center justify-between gap-4 flex-wrap">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium text-gray-900">{{ count($files) }}</span> {{ Str::plural('file', count($files)) }} selected
                        </p>
                        <div class="flex gap-3">
                            <button
                                type="button"
                                wire:click="clearFiles"
                                class="px-5 py-2.5 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg border-2 border-outline transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-gray-200">
                                Clear
                            </button>
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white font-medium rounded-lg shadow-md3-2 hover:shadow-md3-3 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-primary-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="uploadInstantly">
                                    <span class="material-symbols-outlined text-xl">upload</span>
                                </span>
                                <span wire:loading wire:target="uploadInstantly">
                                    <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                </span>
                                <span>Upload Instantly</span>
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        </div>

        <!-- Uploaded Files Grid -->
        @if (!empty($uploaded_files))
            <div class="mt-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Recently Uploaded</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    @foreach ($uploaded_files as $file)
                        <div class="bg-white rounded-xl shadow-md3-1 hover:shadow-md3-3 overflow-hidden transition-all duration-200 hover:-translate-y-1 animate-fade-in">
                            <!-- Thumbnail -->
                            <div class="aspect-square bg-gray-100 relative overflow-hidden group">
                                @if (isset($file['thumbnail_url']) && $file['thumbnail_url'])
                                    <img src="{{ $file['thumbnail_url'] }}" 
                                         alt="{{ $file['original_filename'] ?? 'File' }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <span class="material-symbols-outlined text-4xl text-gray-400">description</span>
                                    </div>
                                @endif

                                <!-- Processing Status -->
                                @if (isset($file['processing_status']) && $file['processing_status'] === 'processing')
                                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                        <div class="text-center">
                                            <div class="w-10 h-10 border-4 border-white border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                                            <span class="text-white text-xs font-medium">Processing...</span>
                                        </div>
                                    </div>
                                @elseif (isset($file['processing_status']) && $file['processing_status'] === 'completed')
                                    <div class="absolute top-2 right-2">
                                        <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center shadow-md3-2">
                                            <span class="material-symbols-outlined text-white text-sm">check</span>
                                        </div>
                                    </div>
                                @elseif (isset($file['processing_status']) && $file['processing_status'] === 'failed')
                                    <div class="absolute inset-0 bg-red-500/10 flex items-center justify-center">
                                        <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center shadow-md3-2">
                                            <span class="material-symbols-outlined text-white">error</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- File Info -->
                            <div class="p-3">
                                <p class="text-xs font-medium text-gray-900 truncate" title="{{ $file['original_filename'] ?? 'Untitled' }}">
                                    {{ $file['original_filename'] ?? 'Untitled' }}
                                </p>
                                <div class="flex items-center justify-between mt-1">
                                    @if (isset($file['file_size']))
                                        <span class="text-xs text-gray-500">
                                            {{ number_format($file['file_size'] / 1024, 0) }} KB
                                        </span>
                                    @endif
                                    @if (isset($file['processing_status']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $file['processing_status'] === 'completed' ? 'bg-green-50 text-green-700' : 
                                               ($file['processing_status'] === 'processing' ? 'bg-blue-50 text-blue-700' : 
                                               'bg-red-50 text-red-700') }}">
                                            <span class="w-1.5 h-1.5 rounded-full 
                                                {{ $file['processing_status'] === 'completed' ? 'bg-green-500' : 
                                                   ($file['processing_status'] === 'processing' ? 'bg-blue-500 animate-pulse' : 
                                                   'bg-red-500') }}">
                                            </span>
                                            {{ ucfirst($file['processing_status']) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- View All Link -->
                <div class="mt-6 text-center">
                    <a href="{{ route('gallery') }}" 
                       class="inline-flex items-center gap-2 px-6 py-3 bg-white hover:bg-gray-50 text-primary-600 font-medium rounded-lg border-2 border-primary-200 hover:border-primary-300 transition-all duration-200 shadow-md3-1 hover:shadow-md3-2">
                        <span>View all photos</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    function uploadManager() {
        return {
            handlePaste(event) {
                const items = (event.clipboardData || event.originalEvent.clipboardData).items;
                for (let item of items) {
                    if (item.type.indexOf('image') !== -1) {
                        const blob = item.getAsFile();
                        // Trigger file input with pasted image
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(blob);
                        document.getElementById('files').files = dataTransfer.files;
                        document.getElementById('files').dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            },
            clearSelection() {
                @this.clearFiles();
            }
        }
    }
</script>

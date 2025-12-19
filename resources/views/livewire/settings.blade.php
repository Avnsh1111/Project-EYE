<div wire:poll.5s="loadModelStatus" class="min-h-screen bg-surface-variant p-4 sm:p-6 lg:p-8">
    <div class="max-w-5xl mx-auto">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-display font-bold text-gray-900 flex items-center gap-3 mb-2">
                <div class="w-12 h-12 bg-gradient-to-br from-gray-600 to-gray-800 rounded-xl flex items-center justify-center shadow-md3-2">
                    <span class="material-symbols-outlined text-white text-2xl">settings</span>
                </div>
                <span>Settings</span>
            </h1>
            <p class="text-base text-gray-600 ml-15">
                Configure AI models and processing options
            </p>
        </div>

        <!-- Success/Error Messages -->
        @if ($saved)
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-xl shadow-md3-1 mb-6 animate-slide-up">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                    <div>
                        <strong class="text-green-800 font-medium">Settings saved successfully!</strong>
                        <p class="text-sm text-green-700 mt-1">Changes will take effect on the next image upload.</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($error)
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-md3-1 mb-6 animate-slide-up">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-red-600">error</span>
                    <div>
                        <strong class="text-red-800 font-medium">Error</strong>
                        <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- AI Service Status Card -->
        <div class="bg-white rounded-2xl shadow-md3-2 p-6 mb-6 animate-fade-in">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2 mb-2">
                        <span class="material-symbols-outlined text-2xl text-gray-700">cloud</span>
                        <span>AI Service Status</span>
                    </h2>
                    <div class="flex items-center gap-2">
                        @if ($ai_service_status === 'online')
                            <span class="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-green-700 text-sm font-medium rounded-full">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                <span>Online</span>
                            </span>
                        @elseif ($ai_service_status === 'offline')
                            <span class="inline-flex items-center gap-2 px-3 py-1 bg-red-50 text-red-700 text-sm font-medium rounded-full">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                <span>Offline</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 px-3 py-1 bg-yellow-50 text-yellow-700 text-sm font-medium rounded-full">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                                <span>Unknown</span>
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex gap-2">
                    <button wire:click="testConnection" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg border-2 border-outline transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-gray-200">
                        <span class="material-symbols-outlined text-lg">refresh</span>
                        <span class="hidden sm:inline">Test</span>
                    </button>
                    <button wire:click="preloadModels" 
                            wire:loading.attr="disabled"
                            wire:target="preloadModels"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md3-2 hover:shadow-md3-3 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-primary-200 disabled:opacity-50">
                        <span wire:loading.remove wire:target="preloadModels" class="material-symbols-outlined text-lg">download</span>
                        <span wire:loading wire:target="preloadModels">
                            <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        </span>
                        <span wire:loading.remove wire:target="preloadModels">Preload Models</span>
                        <span wire:loading wire:target="preloadModels">Loading...</span>
                    </button>
                </div>
            </div>

            <!-- Model Status -->
            @if (!empty($model_status))
                <div class="border-t border-outline pt-6">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Loaded Models</p>
                    
                    @if (isset($model_status['models']) && !empty($model_status['models']))
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                            @foreach ($model_status['models'] as $model)
                                <div class="flex items-center gap-3 px-4 py-3 bg-green-50 rounded-lg">
                                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $model }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (isset($model_status['downloading']) && !empty($model_status['downloading']))
                        <div class="space-y-3 mt-4">
                            <p class="text-sm font-medium text-gray-700">Downloading...</p>
                            @foreach ($model_status['downloading'] as $download)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-900">{{ $download['model'] ?? 'Unknown' }}</span>
                                        <span class="text-sm text-gray-600">{{ $download['progress'] ?? '0' }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div class="bg-primary-600 h-full rounded-full transition-all duration-300" 
                                             x-bind:style="'width: ' + {{ $download['progress'] ?? 0 }} + '%'"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- AI Model Configuration -->
        <form wire:submit.prevent="saveSettings">
            <div class="bg-white rounded-2xl shadow-md3-2 overflow-hidden mb-6">
                <div class="p-6 border-b border-outline">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-2xl text-gray-700">psychology</span>
                        <span>AI Model Configuration</span>
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Select models for image analysis and processing</p>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Captioning Model -->
                    <div>
                        <label for="captioning_model" class="block text-sm font-medium text-gray-700 mb-2">
                            Captioning Model
                        </label>
                        <select 
                            id="captioning_model"
                            wire:model="captioning_model" 
                            class="w-full px-4 py-3 bg-surface-variant border-2 border-outline rounded-xl text-gray-900 focus:outline-none focus:border-primary-600 focus:ring-4 focus:ring-primary-50 transition-all duration-200">
                            <option value="">Select a model</option>
                            <option value="florence">Florence-2 (Recommended)</option>
                            <option value="blip">BLIP</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500">Generates descriptions for images</p>
                    </div>

                    <!-- Object Detection Model -->
                    <div>
                        <label for="object_detection_model" class="block text-sm font-medium text-gray-700 mb-2">
                            Object Detection Model
                        </label>
                        <select 
                            id="object_detection_model"
                            wire:model="object_detection_model" 
                            class="w-full px-4 py-3 bg-surface-variant border-2 border-outline rounded-xl text-gray-900 focus:outline-none focus:border-primary-600 focus:ring-4 focus:ring-primary-50 transition-all duration-200">
                            <option value="">Select a model</option>
                            <option value="clip">CLIP (Recommended)</option>
                            <option value="florence">Florence-2</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500">Detects objects and labels in images</p>
                    </div>

                    <!-- LLM Model -->
                    <div>
                        <label for="llm_model" class="block text-sm font-medium text-gray-700 mb-2">
                            LLM Model (Ollama)
                        </label>
                        <select 
                            id="llm_model"
                            wire:model="llm_model" 
                            class="w-full px-4 py-3 bg-surface-variant border-2 border-outline rounded-xl text-gray-900 focus:outline-none focus:border-primary-600 focus:ring-4 focus:ring-primary-50 transition-all duration-200">
                            <option value="">None (Disable)</option>
                            <option value="llava:latest">LLaVA (Recommended)</option>
                            <option value="llava:13b">LLaVA 13B (Higher Quality)</option>
                            <option value="llava:7b">LLaVA 7B (Faster)</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500">Advanced vision-language model for detailed analysis</p>
                    </div>

                    <!-- Enable Face Recognition -->
                    <div class="flex items-start gap-3 p-4 bg-surface-variant rounded-xl">
                        <input 
                            type="checkbox" 
                            id="enable_face_recognition"
                            wire:model="enable_face_recognition"
                            class="mt-1 w-5 h-5 text-primary-600 border-2 border-outline rounded focus:ring-2 focus:ring-primary-500 transition-all duration-200 cursor-pointer"
                        >
                        <div class="flex-1">
                            <label for="enable_face_recognition" class="block text-sm font-medium text-gray-900 cursor-pointer">
                                Enable Face Recognition
                            </label>
                            <p class="mt-1 text-sm text-gray-600">
                                Automatically detect and group faces in photos
                            </p>
                        </div>
                    </div>

                    <!-- Enable Intelligent Reprocessing -->
                    <div class="flex items-start gap-3 p-4 bg-surface-variant rounded-xl">
                        <input 
                            type="checkbox" 
                            id="intelligent_reprocessing"
                            wire:model="intelligent_reprocessing"
                            class="mt-1 w-5 h-5 text-primary-600 border-2 border-outline rounded focus:ring-2 focus:ring-primary-500 transition-all duration-200 cursor-pointer"
                        >
                        <div class="flex-1">
                            <label for="intelligent_reprocessing" class="block text-sm font-medium text-gray-900 cursor-pointer">
                                Intelligent Reprocessing
                            </label>
                            <p class="mt-1 text-sm text-gray-600">
                                Only reprocess images when model configuration changes
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3">
                <button 
                    type="button"
                    wire:click="$refresh"
                    class="px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg border-2 border-outline transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-gray-200">
                    Reset
                </button>
                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white font-medium rounded-lg shadow-md3-2 hover:shadow-md3-3 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-primary-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="saveSettings">
                        <span class="material-symbols-outlined text-xl">save</span>
                    </span>
                    <span wire:loading wire:target="saveSettings">
                        <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    </span>
                    <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                    <span wire:loading wire:target="saveSettings">Saving...</span>
                </button>
            </div>
        </form>

    </div>
</div>

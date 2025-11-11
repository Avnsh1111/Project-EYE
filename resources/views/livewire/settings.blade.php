<div wire:poll.5s="loadModelStatus">
    <!-- Settings Header -->
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.5rem;">
            Settings
        </h1>
        <p style="font-size: 0.875rem; color: var(--secondary-color);">
            Configure AI models and processing options
        </p>
    </div>

    <!-- AI Service Status -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div>
                <h2 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem; color: #202124;">
                    <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">cloud</span>
                    AI Service Status
                </h2>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    @if ($ai_service_status === 'online')
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem; color: #137333;">
                            <span style="width: 8px; height: 8px; background: #137333; border-radius: 50%;"></span>
                            Online
                        </span>
                    @elseif ($ai_service_status === 'offline')
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem; color: #d93025;">
                            <span style="width: 8px; height: 8px; background: #d93025; border-radius: 50%;"></span>
                            Offline
                        </span>
                    @else
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem; color: #f9ab00;">
                            <span style="width: 8px; height: 8px; background: #f9ab00; border-radius: 50%;"></span>
                            Unknown
                        </span>
                    @endif
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button wire:click="testConnection" class="btn btn-secondary">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">refresh</span>
                    Test Connection
                </button>
                <button wire:click="preloadModels" class="btn btn-primary" wire:loading.attr="disabled" wire:target="preloadModels">
                    <span wire:loading.remove wire:target="preloadModels" class="material-symbols-outlined" style="font-size: 1.125rem;">download</span>
                    <span wire:loading wire:target="preloadModels" class="spinner" style="width: 20px; height: 20px; margin: 0;"></span>
                    <span wire:loading.remove wire:target="preloadModels">Preload Models</span>
                    <span wire:loading wire:target="preloadModels">Loading...</span>
                </button>
            </div>
        </div>
        
        <!-- Model Status -->
        @if (!empty($model_status))
            <div style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <div style="font-size: 0.875rem; font-weight: 500; color: var(--secondary-color); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    Loaded Models
                </div>
                
                @if (isset($model_status['models']) && !empty($model_status['models']))
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        @foreach ($model_status['models'] as $model)
                            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                                <span style="color: #137333;" class="material-symbols-outlined" style="font-size: 1rem;">check_circle</span>
                                <span style="color: #202124;">{{ $model }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                @if (isset($model_status['downloading']) && !empty($model_status['downloading']))
                    <div style="margin-top: 0.75rem;">
                        <div style="font-size: 0.875rem; font-weight: 500; color: var(--secondary-color); margin-bottom: 0.5rem;">
                            Downloading...
                        </div>
                        @foreach ($model_status['downloading'] as $download)
                            <div style="margin-bottom: 0.75rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.75rem; color: #202124;">{{ $download['model'] ?? 'Unknown' }}</span>
                                    <span style="font-size: 0.75rem; color: var(--secondary-color);">{{ $download['progress'] ?? '0' }}%</span>
                                </div>
                                <div style="height: 6px; background: var(--hover-bg); border-radius: 3px; overflow: hidden;">
                                    <div style="height: 100%; background: var(--primary-color); width: {{ $download['progress'] ?? 0 }}%; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Success/Error Messages -->
    @if ($saved)
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            <span class="material-symbols-outlined">check_circle</span>
            <div>
                <strong>Settings saved successfully!</strong>
                <div style="font-size: 0.875rem; margin-top: 0.25rem;">
                    Changes will take effect on the next image upload.
                </div>
            </div>
        </div>
    @endif

    @if ($error)
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <span class="material-symbols-outlined">error</span>
            <div>
                <strong>Error</strong>
                <div style="font-size: 0.875rem; margin-top: 0.25rem;">{{ $error }}</div>
            </div>
        </div>
    @endif

    <form wire:submit.prevent="save">
        <!-- Image Captioning Model -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 1rem; color: #202124;">
                <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">description</span>
                Image Captioning Model
            </h2>
            <p style="font-size: 0.875rem; color: var(--secondary-color); margin-bottom: 1rem;">
                The model used to generate text descriptions of images.
            </p>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach ($available_captioning_models as $model_id => $model_name)
                    <label style="display: flex; align-items: start; gap: 0.75rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: var(--transition);"
                           onmouseover="this.style.background='var(--hover-bg)'"
                           onmouseout="this.style.background='white'"
                           onclick="this.querySelector('input').checked = true; @this.set('captioning_model', '{{ $model_id }}')">
                        <input type="radio" 
                               wire:model="captioning_model" 
                               value="{{ $model_id }}"
                               style="margin-top: 0.25rem; width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);">
                        <div style="flex: 1;">
                            <div style="font-weight: 500; color: #202124; margin-bottom: 0.25rem;">
                                {{ $model_name }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--secondary-color); font-family: monospace;">
                                {{ $model_id }}
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Image Embedding Model -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 1rem; color: #202124;">
                <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">search</span>
                Image Embedding Model
            </h2>
            <p style="font-size: 0.875rem; color: var(--secondary-color); margin-bottom: 1rem;">
                The model used to generate vector embeddings for semantic search.
            </p>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach ($available_embedding_models as $model_id => $model_name)
                    <label style="display: flex; align-items: start; gap: 0.75rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: var(--transition);"
                           onmouseover="this.style.background='var(--hover-bg)'"
                           onmouseout="this.style.background='white'"
                           onclick="this.querySelector('input').checked = true; @this.set('embedding_model', '{{ $model_id }}')">
                        <input type="radio" 
                               wire:model="embedding_model" 
                               value="{{ $model_id }}"
                               style="margin-top: 0.25rem; width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);">
                        <div style="flex: 1;">
                            <div style="font-weight: 500; color: #202124; margin-bottom: 0.25rem;">
                                {{ $model_name }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--secondary-color); font-family: monospace;">
                                {{ $model_id }}
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Ollama Settings -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 1rem; color: #202124;">
                <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">auto_awesome</span>
                Ollama (Detailed Descriptions)
            </h2>
            <p style="font-size: 0.875rem; color: var(--secondary-color); margin-bottom: 1rem;">
                Use Ollama for generating detailed, comprehensive image descriptions. Requires Ollama to be installed and running.
            </p>

            <!-- Ollama Status Alert -->
            @if (isset($model_status['ollama_available']))
                @if ($model_status['ollama_available'])
                    <div style="padding: 0.75rem 1rem; background: #e6f4ea; border: 1px solid #137333; border-radius: 8px; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: #137333;">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">check_circle</span>
                            <span style="font-size: 0.875rem; font-weight: 500;">Ollama Server is Running</span>
                        </div>
                    </div>
                @else
                    <div style="padding: 0.75rem 1rem; background: #fce8e6; border: 1px solid #d93025; border-radius: 8px; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: start; gap: 0.5rem; color: #d93025;">
                            <span class="material-symbols-outlined" style="font-size: 1rem; margin-top: 0.125rem;">error</span>
                            <div style="flex: 1;">
                                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">Ollama Server Not Detected</div>
                                <div style="font-size: 0.75rem; opacity: 0.9;">
                                    Install Ollama from <a href="https://ollama.com" target="_blank" style="color: #d93025; text-decoration: underline;">ollama.com</a> and run: <code style="background: rgba(0,0,0,0.1); padding: 0.125rem 0.5rem; border-radius: 4px; font-family: monospace;">ollama pull llava</code>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Enable Ollama -->
            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; margin-bottom: 1rem;">
                <input type="checkbox" 
                       wire:model="ollama_enabled"
                       style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);">
                <div style="flex: 1;">
                    <div style="font-weight: 500; color: #202124;">
                        Enable Ollama
                    </div>
                    <div style="font-size: 0.75rem; color: var(--secondary-color);">
                        Generate detailed AI descriptions using local LLM (3-4 sentences vs 1 sentence)
                    </div>
                </div>
            </label>

            <!-- Ollama Model Selection -->
            @if ($ollama_enabled)
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--secondary-color); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Ollama Model
                    </label>
                    <select wire:model="ollama_model" 
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.875rem;">
                        @foreach ($available_ollama_models as $model_id => $model_name)
                            <option value="{{ $model_id }}">{{ $model_name }}</option>
                        @endforeach
                    </select>
                    <div style="font-size: 0.75rem; color: var(--secondary-color); margin-top: 0.5rem;">
                        Make sure this model is pulled: <code style="background: var(--hover-bg); padding: 0.125rem 0.5rem; border-radius: 4px; font-family: monospace;">ollama pull {{ $ollama_model }}</code>
                    </div>
                </div>
            @endif
            
            <!-- Setup Guide Link -->
            <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: #e8f0fe; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="material-symbols-outlined" style="font-size: 1rem; color: var(--primary-color);">info</span>
                    <span style="font-size: 0.875rem; color: #202124;">
                        New to Ollama? See <a href="/docs/OLLAMA_SETUP.md" target="_blank" style="color: var(--primary-color); text-decoration: underline; font-weight: 500;">Setup Guide</a> for installation instructions.
                    </span>
                </div>
            </div>
        </div>

        <!-- Face Detection -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 1rem; color: #202124;">
                <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">face</span>
                Face Detection
            </h2>
            <p style="font-size: 0.875rem; color: var(--secondary-color); margin-bottom: 1rem;">
                Automatically detect and count faces in uploaded images.
            </p>

            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer;">
                <input type="checkbox" 
                       wire:model="face_detection_enabled"
                       style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);">
                <div style="flex: 1;">
                    <div style="font-weight: 500; color: #202124;">
                        Enable Face Detection
                    </div>
                    <div style="font-size: 0.75rem; color: var(--secondary-color);">
                        Detect and count faces using face_recognition library
                    </div>
                </div>
            </label>
        </div>

        <!-- Save Button -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button type="button" 
                    wire:click="loadSettings" 
                    class="btn btn-secondary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">refresh</span>
                Reset
            </button>
            <button type="submit" 
                    class="btn btn-primary"
                    wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save" class="material-symbols-outlined" style="font-size: 1.125rem;">save</span>
                <span wire:loading wire:target="save" class="spinner" style="width: 20px; height: 20px; margin: 0;"></span>
                <span wire:loading.remove wire:target="save">Save Settings</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>

    <!-- Info Box -->
    <div class="alert alert-info" style="margin-top: 2rem;">
        <span class="material-symbols-outlined">info</span>
        <div>
            <strong>Note</strong>
            <div style="font-size: 0.875rem; margin-top: 0.25rem;">
                When you change models, the Python service will download them on first use (~2-5 GB per model). 
                This is a one-time download and models are cached for future use.
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-hide success message after 5 seconds
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('setting-saved', () => {
            setTimeout(() => {
                @this.set('saved', false);
            }, 5000);
        });
    });
</script>


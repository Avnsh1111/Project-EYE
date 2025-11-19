<div>
    <!-- Upload Header -->
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.5rem;">
            Upload photos
        </h1>
        <p style="font-size: 0.875rem; color: var(--secondary-color);">
            Upload multiple images to analyze with AI. Each image will be automatically analyzed for descriptions and searchable embeddings.
        </p>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="alert alert-error">
            <span class="material-symbols-outlined">error</span>
            <div>
                <strong>Validation errors</strong>
                <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.875rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Processing Errors -->
    @if (!empty($errors_list))
        <div class="alert alert-error">
            <span class="material-symbols-outlined">error</span>
            <div>
                <strong>Processing errors</strong>
                <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.875rem;">
                    @foreach ($errors_list as $error)
                        <li><strong>{{ $error['filename'] }}:</strong> {{ $error['error'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Upload Form -->
    <div class="card">
        <form wire:submit.prevent="processImages">
            <!-- File Upload Area -->
            <label for="images" class="file-upload-area" style="position: relative; min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <!-- Normal State -->
                <span wire:loading.remove wire:target="images" class="material-symbols-outlined" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 1rem;">
                    @if (!empty($images))
                        check_circle
                    @else
                        cloud_upload
                    @endif
                </span>
                <div wire:loading.remove wire:target="images" style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem; color: #202124;">
                    @if (!empty($images))
                        {{ count($images) }} {{ Str::plural('file', count($images)) }} selected
                    @else
                        Click to upload or drag and drop
                    @endif
                </div>
                <div wire:loading.remove wire:target="images" style="color: var(--secondary-color); font-size: 0.875rem;">
                    PNG, JPG, GIF, WEBP up to 10MB each
                </div>

                <!-- Loading State -->
                <div wire:loading wire:target="images" class="spinner"></div>
                <div wire:loading wire:target="images" style="font-size: 1rem; color: var(--secondary-color); margin-top: 1rem;">Loading files...</div>
            </label>

            <input
                type="file"
                id="images"
                wire:model="images"
                multiple
                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                style="display: none;"
            >

            <!-- Action Buttons -->
            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button
                    type="submit"
                    class="btn btn-primary"
                    style="flex: 1;"
                    wire:loading.attr="disabled"
                    wire:target="images,processImages"
                    @disabled($processing || empty($images))
                >
                    <span wire:loading.remove wire:target="processImages" class="material-symbols-outlined" style="font-size: 1.125rem;">
                        psychology
                    </span>
                    <span wire:loading wire:target="processImages" class="spinner" style="width: 20px; height: 20px; margin: 0;"></span>
                    <span wire:loading.remove wire:target="processImages">Analyze images</span>
                    <span wire:loading wire:target="processImages">Processing...</span>
                </button>

                @if (!empty($results) || !empty($errors_list))
                    <button
                        type="button"
                        wire:click="clear"
                        class="btn btn-secondary"
                        wire:loading.attr="disabled"
                    >
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">refresh</span>
                        Clear
                    </button>
                @endif
            </div>
        </form>

        <!-- Processing Progress Bar -->
        @if ($processing && $progress['total'] > 0)
            <div style="margin-top: 2rem; padding: 1.5rem; background: var(--hover-bg); border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="font-weight: 500; color: #202124;">Processing images...</span>
                    <span style="color: var(--primary-color); font-weight: 500;">{{ $progress['current'] }} / {{ $progress['total'] }}</span>
                </div>
                <div style="width: 100%; height: 8px; background: white; border-radius: 10px; overflow: hidden;">
                    <div style="height: 100%; background: var(--primary-color); transition: width 0.3s ease; width: {{ $progress['percentage'] }}%;"></div>
                </div>
                <div style="text-align: center; color: var(--secondary-color); font-size: 0.875rem; margin-top: 0.75rem;">
                    {{ $progress['percentage'] }}% complete
                </div>
            </div>
        @endif
    </div>

    <!-- Success Message and Results -->
    @if (!empty($results))
        <div class="alert alert-success" style="margin-top: 1.5rem;">
            <span class="material-symbols-outlined">check_circle</span>
            <div>
                <strong>Success!</strong>
                <div style="font-size: 0.875rem; margin-top: 0.25rem;">
                    Successfully processed {{ count($results) }} {{ Str::plural('image', count($results)) }}
                </div>
            </div>
        </div>

        <!-- Results Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin: 2rem 0 1rem;">
            <h2 style="font-size: 1.25rem; font-weight: 500; color: #202124;">
                Analysis results
            </h2>
            <a wire:navigate href="{{ route('gallery') }}" class="btn btn-secondary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">photo_library</span>
                View in gallery
            </a>
        </div>

        <!-- Results Grid -->
        <div class="photos-grid">
            @foreach ($results as $result)
                <div class="photo-item">
                    <img src="{{ $result['url'] }}" alt="{{ $result['filename'] }}" loading="lazy">
                    
                    <!-- Hover Overlay -->
                    <div class="photo-overlay">
                        <div class="photo-overlay-title">
                            {{ Str::limit($result['description'], 60) }}
                        </div>
                        <div class="photo-overlay-meta" style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: rgba(16, 185, 129, 0.2); padding: 0.25rem 0.5rem; border-radius: 12px;">
                                <span class="material-symbols-outlined" style="font-size: 0.875rem;">check_circle</span>
                                Analyzed
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

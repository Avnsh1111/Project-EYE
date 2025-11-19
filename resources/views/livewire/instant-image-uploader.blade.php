<div>
    <!-- Upload Header -->
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.5rem;">
            ⚡ Instant Upload
        </h1>
        <p style="font-size: 0.875rem; color: var(--secondary-color);">
            Upload photos instantly! AI analysis happens in the background. You can continue browsing immediately.
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

    <!-- Upload Form -->
    <div class="card">
        <form wire:submit.prevent="uploadInstantly">
            <!-- File Upload Area -->
            <label for="images" class="file-upload-area" style="position: relative; min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <!-- Normal State -->
                <span wire:loading.remove wire:target="images" class="material-symbols-outlined" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 1rem;">
                    @if (!empty($images))
                        check_circle
                    @else
                        bolt
                    @endif
                </span>
                <div wire:loading.remove wire:target="images" style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem; color: #202124;">
                    @if (!empty($images))
                        {{ count($images) }} {{ Str::plural('file', count($images)) }} selected
                    @else
                        ⚡ Instant Upload - No Waiting!
                    @endif
                </div>
                <div wire:loading.remove wire:target="images" style="color: var(--secondary-color); font-size: 0.875rem;">
                    Click to select • Drag and drop • Process in background
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
                    wire:target="images,uploadInstantly"
                    @disabled($uploading || empty($images))
                >
                    <span wire:loading.remove wire:target="uploadInstantly" class="material-symbols-outlined" style="font-size: 1.125rem;">
                        bolt
                    </span>
                    <span wire:loading wire:target="uploadInstantly" class="spinner" style="width: 20px; height: 20px; margin: 0;"></span>
                    <span wire:loading.remove wire:target="uploadInstantly">⚡ Upload Instantly</span>
                    <span wire:loading wire:target="uploadInstantly">Uploading...</span>
                </button>

                @if (!empty($uploaded_images))
                    <button
                        type="button"
                        wire:click="clearUploaded"
                        class="btn btn-secondary"
                        wire:loading.attr="disabled"
                    >
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">refresh</span>
                        Clear
                    </button>
                @endif
            </div>
        </form>

        <!-- Upload Progress -->
        @if ($uploading && $total_files > 0)
            <div style="margin-top: 2rem; padding: 1.5rem; background: var(--hover-bg); border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="font-weight: 500; color: #202124;">Uploading files...</span>
                    <span style="color: var(--primary-color); font-weight: 500;">{{ $uploaded_count }} / {{ $total_files }}</span>
                </div>
                <div style="width: 100%; height: 8px; background: white; border-radius: 10px; overflow: hidden;">
                    <div style="height: 100%; background: var(--primary-color); transition: width 0.3s ease; width: {{ $total_files > 0 ? ($uploaded_count / $total_files) * 100 : 0 }}%;"></div>
                </div>
            </div>
        @endif
    </div>

    <!-- Uploaded Images (Processing in Background) -->
    @if (!empty($uploaded_images))
        <div class="alert alert-success" style="margin-top: 1.5rem;">
            <span class="material-symbols-outlined">check_circle</span>
            <div>
                <strong>✅ {{ count($uploaded_images) }} {{ Str::plural('image', count($uploaded_images)) }} uploaded!</strong>
                <div style="font-size: 0.875rem; margin-top: 0.25rem;">
                    AI analysis is processing in the background. You can continue browsing.
                </div>
            </div>
        </div>

        <!-- Processing Status Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin: 2rem 0 1rem;">
            <h2 style="font-size: 1.25rem; font-weight: 500; color: #202124;">
                Background Processing
            </h2>
            <a wire:navigate href="{{ route('processing-status') }}" class="btn btn-secondary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">query_stats</span>
                View All Processing
            </a>
        </div>

        <!-- Recently Uploaded (Quick Preview) -->
        <div class="photos-grid">
            @foreach ($uploaded_images as $img)
                <div class="photo-item" style="position: relative;">
                    <img src="{{ $img['url'] }}" alt="{{ $img['filename'] }}" loading="lazy">
                    
                    <!-- Processing Badge -->
                    <div class="photo-overlay" style="background: rgba(0,0,0,0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 1;">
                        <div class="spinner" style="margin-bottom: 0.5rem;"></div>
                        <div style="color: white; font-size: 0.875rem; font-weight: 500;">
                            Processing...
                        </div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-top: 0.25rem;">
                            {{ $img['filename'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Info Banner -->
        <div style="margin-top: 1.5rem; padding: 1rem; background: #e8f0fe; border-radius: 8px; border-left: 4px solid var(--primary-color);">
            <div style="display: flex; align-items: start; gap: 0.75rem;">
                <span class="material-symbols-outlined" style="color: var(--primary-color); font-size: 1.25rem;">info</span>
                <div style="font-size: 0.875rem; color: #202124;">
                    <strong>Background Processing Active</strong>
                    <p style="margin: 0.25rem 0 0 0; color: var(--secondary-color);">
                        Your images are being analyzed with deep AI processing. This includes:
                    </p>
                    <ul style="margin: 0.5rem 0 0 1.5rem; color: var(--secondary-color);">
                        <li>Detailed image captioning</li>
                        <li>Vector embeddings for semantic search</li>
                        <li>Face detection and encoding</li>
                        <li>Advanced metadata extraction</li>
                        <li>AI-generated tags</li>
                    </ul>
                    <p style="margin: 0.5rem 0 0 0; color: var(--secondary-color);">
                        You'll see real-time updates in the <a wire:navigate href="{{ route('processing-status') }}" style="color: var(--primary-color); text-decoration: underline;">Processing Status</a> page.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
// Listen for upload completion
document.addEventListener('livewire:initialized', () => {
    Livewire.on('upload-complete', (event) => {
        // Optional: Show notification
        console.log(`${event.count} images uploaded and queued for processing`);
        
        // Optional: Redirect to processing status after 3 seconds
        setTimeout(() => {
            // window.location.href = '{{ route("processing-status") }}';
        }, 3000);
    });
});
</script>


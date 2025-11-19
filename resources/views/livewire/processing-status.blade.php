<div>
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.5rem;">
                Background Processing Status
            </h1>
            <p style="font-size: 0.875rem; color: var(--secondary-color);">
                Click Refresh to update • Manual refresh
            </p>
        </div>
        
        <button wire:click="loadStatus" class="btn btn-secondary">
            <span class="material-symbols-outlined" style="font-size: 1.125rem;">refresh</span>
            Refresh
        </button>
    </div>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <!-- Pending -->
        <div class="card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: 700; color: #f9ab00; margin-bottom: 0.5rem;">
                {{ $stats['pending'] }}
            </div>
            <div style="font-size: 0.875rem; color: var(--secondary-color);">
                ⏳ Pending
            </div>
        </div>

        <!-- Processing -->
        <div class="card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color); margin-bottom: 0.5rem;">
                {{ $stats['processing'] }}
            </div>
            <div style="font-size: 0.875rem; color: var(--secondary-color);">
                ⚙️ Processing
            </div>
        </div>

        <!-- Completed -->
        <div class="card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: 700; color: #137333; margin-bottom: 0.5rem;">
                {{ $stats['completed'] }}
            </div>
            <div style="font-size: 0.875rem; color: var(--secondary-color);">
                ✅ Completed
            </div>
        </div>

        <!-- Failed -->
        <div class="card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: 700; color: #d93025; margin-bottom: 0.5rem;">
                {{ $stats['failed'] }}
            </div>
            <div style="font-size: 0.875rem; color: var(--secondary-color);">
                ❌ Failed
            </div>
        </div>
    </div>

    <!-- Currently Processing -->
    @if (!empty($processing_images))
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 500; color: #202124; margin-bottom: 1rem;">
                ⚙️ Currently Processing ({{ count($processing_images) }})
            </h2>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach ($processing_images as $img)
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--hover-bg); border-radius: 8px;">
                        <img src="{{ $img['url'] }}" alt="{{ $img['filename'] }}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                        
                        <div style="flex: 1;">
                            <div style="font-weight: 500; color: #202124; margin-bottom: 0.25rem;">
                                {{ $img['filename'] }}
                            </div>
                            <div style="font-size: 0.875rem; color: var(--secondary-color);">
                                Started: {{ $img['started_at'] }}
                            </div>
                        </div>
                        
                        <div class="spinner"></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Recently Completed (Last 24h) -->
    @if (!empty($completed_images))
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 500; color: #202124; margin-bottom: 1rem;">
                ✅ Recently Completed (Last 24h)
            </h2>
            
            <div class="photos-grid">
                @foreach ($completed_images as $img)
                    <div class="photo-item">
                        <img src="{{ $img['url'] }}" alt="{{ $img['filename'] }}" loading="lazy">
                        
                        <div class="photo-overlay">
                            <div class="photo-overlay-title">
                                {{ Str::limit($img['description'], 50) }}
                            </div>
                            <div class="photo-overlay-meta">
                                <span style="color: #137333;">✓</span> {{ $img['processing_time'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Failed Processing -->
    @if (!empty($failed_images))
        <div class="card">
            <h2 style="font-size: 1.25rem; font-weight: 500; color: #202124; margin-bottom: 1rem;">
                ❌ Failed Processing ({{ count($failed_images) }})
            </h2>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach ($failed_images as $img)
                    <div style="display: flex; align-items: start; gap: 1rem; padding: 1rem; background: #fce8e6; border-radius: 8px; border-left: 4px solid #d93025;">
                        <img src="{{ $img['url'] }}" alt="{{ $img['filename'] }}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                        
                        <div style="flex: 1;">
                            <div style="font-weight: 500; color: #202124; margin-bottom: 0.25rem;">
                                {{ $img['filename'] }}
                            </div>
                            <div style="font-size: 0.875rem; color: #d93025; margin-bottom: 0.5rem;">
                                Error: {{ $img['error'] }}
                            </div>
                        </div>
                        
                        <button wire:click="retryFailed({{ $img['id'] }})" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">refresh</span>
                            Retry
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Empty State -->
    @if (empty($processing_images) && empty($completed_images) && empty($failed_images))
        <div class="empty-state">
            <div class="empty-state-icon">✅</div>
            <h2 class="empty-state-title">All Caught Up!</h2>
            <p class="empty-state-description">No images currently processing</p>
            <a wire:navigate href="{{ route('instant-upload') }}" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">bolt</span>
                Upload More Images
            </a>
        </div>
    @endif
</div>


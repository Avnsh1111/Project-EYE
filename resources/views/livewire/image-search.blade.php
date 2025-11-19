<div>
    <!-- Search Header -->
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.5rem;">
            Search your photos
        </h1>
        <p style="font-size: 0.875rem; color: var(--secondary-color);">
            Use natural language to find images based on their content
        </p>
    </div>

    <!-- Search Stats -->
    @if (!empty($results) || $stats['total_images'] > 0)
        <div style="display: flex; gap: 2rem; margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
            <div>
                <div style="font-size: 1.5rem; font-weight: 500; color: #202124;">{{ $stats['total_images'] }}</div>
                <div style="font-size: 0.875rem; color: var(--secondary-color);">Total photos</div>
            </div>
            @if (!empty($results))
                <div>
                    <div style="font-size: 1.5rem; font-weight: 500; color: var(--primary-color);">{{ count($results) }}</div>
                    <div style="font-size: 0.875rem; color: var(--secondary-color);">Results found</div>
                </div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: 500; color: #137333;">{{ $stats['search_time'] }}ms</div>
                    <div style="font-size: 0.875rem; color: var(--secondary-color);">Search time</div>
                </div>
            @endif
        </div>
    @endif

    <!-- Error Alert -->
    @if ($error)
        <div class="alert alert-error">
            <span class="material-symbols-outlined">error</span>
            <div>
                <strong>Error</strong>
                <div style="font-size: 0.875rem;">{{ $error }}</div>
            </div>
        </div>
    @endif

    <!-- Search Form -->
    <div class="card">
        <form wire:submit.prevent="search">
            <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem;">
                <div style="flex: 1; position: relative;">
                    <span class="material-symbols-outlined" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--secondary-color); pointer-events: none;">
                        search
                    </span>
                    <input
                        type="text"
                        wire:model.live="query"
                        placeholder="e.g., 'person wearing glasses', 'blue car', 'sunset over mountains'..."
                        style="width: 100%; padding: 0.875rem 1rem 0.875rem 3rem; border: 1px solid var(--border-color); border-radius: 24px; font-size: 1rem; transition: var(--transition);"
                        wire:loading.attr="disabled"
                        wire:target="search"
                        onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='var(--shadow-sm)'"
                        onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"
                    >
                </div>
                <button
                    type="submit"
                    class="btn btn-primary"
                    wire:loading.attr="disabled"
                    wire:target="search"
                    @disabled($searching || strlen($query) < 3)
                >
                    <span wire:loading.remove wire:target="search" class="material-symbols-outlined" style="font-size: 1.125rem;">
                        search
                    </span>
                    <span wire:loading wire:target="search" class="spinner" style="width: 20px; height: 20px; margin: 0;"></span>
                    <span wire:loading.remove wire:target="search">Search</span>
                    <span wire:loading wire:target="search">Searching...</span>
                </button>
                @if (!empty($results) || $error)
                    <button
                        type="button"
                        wire:click="clear"
                        class="btn btn-secondary"
                        wire:loading.attr="disabled"
                    >
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">close</span>
                        Clear
                    </button>
                @endif
            </div>

            <!-- Options -->
            <div style="display: flex; align-items: center; gap: 1rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; color: var(--secondary-color);">
                    <input
                        type="checkbox"
                        wire:model="showScores"
                        wire:click="toggleScores"
                        style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);"
                    >
                    <span>Show similarity scores</span>
                </label>
            </div>
        </form>
    </div>

    <!-- Loading Spinner -->
    @if ($searching)
        <div style="text-align: center; padding: 3rem;">
            <div class="spinner"></div>
            <p style="color: var(--secondary-color); margin-top: 1rem;">Analyzing your query...</p>
        </div>
    @endif

    <!-- Search Results -->
    @if (!$searching && $query && !$error)
        <div style="margin-top: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 500; color: #202124; margin-bottom: 1rem;">
                Search results for "{{ $query }}"
            </h2>

            @if (count($results) === 0)
                <div class="empty-state">
                    <div class="empty-state-icon">🔍</div>
                    <h3 class="empty-state-title">No matches found</h3>
                    <p class="empty-state-description">
                        No images match your search. Try:
                        <ul style="text-align: left; margin: 1rem auto; max-width: 400px; color: var(--secondary-color);">
                            <li>Different keywords</li>
                            <li>More general terms</li>
                            <li>Uploading more images</li>
                        </ul>
                    </p>
                    <button wire:click="clear" class="btn btn-secondary">
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">refresh</span>
                        Clear search
                    </button>
                </div>
            @else
                <!-- Search Stats -->
                <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <p style="font-size: 0.875rem; color: var(--secondary-color);">
                        Found {{ count($results) }} {{ Str::plural('result', count($results)) }} in {{ $stats['search_time'] }}ms
                    </p>
                    <button wire:click="toggleScores" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                        <span class="material-symbols-outlined" style="font-size: 1rem;">{{ $showScores ? 'visibility_off' : 'visibility' }}</span>
                        {{ $showScores ? 'Hide' : 'Show' }} Scores
                    </button>
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
                                @if ($showScores)
                                    <div class="photo-overlay-meta" style="display: flex; flex-direction: column; gap: 0.25rem; align-items: flex-start;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: {{ $result['similarity'] >= 90 ? 'rgba(16, 185, 129, 0.9)' : 'rgba(59, 130, 246, 0.9)' }}; padding: 0.25rem 0.5rem; border-radius: 12px;">
                                            <span class="material-symbols-outlined" style="font-size: 0.875rem;">{{ $result['match_type'] == 'exact' ? 'check_circle' : 'search' }}</span>
                                            {{ $result['similarity'] }}% {{ $result['match_type'] == 'exact' ? 'Exact' : 'Match' }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- Empty State - No Images -->
    @if (empty($results) && !$searching && !$error && $stats['total_images'] === 0)
        <div class="empty-state">
            <div class="empty-state-icon">📸</div>
            <h3 class="empty-state-title">No photos to search</h3>
            <p class="empty-state-description">Upload some photos first to start searching</p>
            <a wire:navigate href="{{ route('instant-upload') }}" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">upload</span>
                Upload photos
            </a>
        </div>
    @endif
</div>

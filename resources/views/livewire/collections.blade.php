<div>
    <!-- Header -->
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.5rem;">
            Collections
        </h1>
        <p style="font-size: 0.875rem; color: var(--secondary-color);">
            Your photos organized by AI-detected categories and faces
        </p>
    </div>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="card" style="padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📸</div>
            <div style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.25rem;">
                {{ $stats['total_images'] }}
            </div>
            <div style="font-size: 0.875rem; color: var(--secondary-color);">Total Photos</div>
        </div>
        
        <div class="card" style="padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏷️</div>
            <div style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.25rem;">
                {{ $stats['total_categories'] }}
            </div>
            <div style="font-size: 0.875rem; color: var(--secondary-color);">Categories</div>
        </div>
        
        <div class="card" style="padding: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
            <div style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.25rem;">
                {{ $stats['total_faces'] }}
            </div>
            <div style="font-size: 0.875rem; color: var(--secondary-color);">Photos with Faces</div>
        </div>
    </div>

    <!-- Face Collections -->
    @if (!empty($faceCollections))
        <div style="margin-bottom: 3rem;">
            <h2 style="font-size: 1.25rem; font-weight: 500; color: #202124; margin-bottom: 1rem;">
                <span class="material-symbols-outlined" style="font-size: 1.5rem; vertical-align: middle; margin-right: 0.5rem;">face</span>
                People
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                @foreach ($faceCollections as $collection)
                    <a wire:navigate href="{{ route('gallery') }}?faces={{ $collection['name'] }}" 
                       class="collection-card"
                       style="text-decoration: none; color: inherit; display: block; transition: var(--transition);">
                        <div class="card" style="padding: 0; overflow: hidden; cursor: pointer; transition: var(--transition);"
                             onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='var(--shadow-md)'"
                             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            
                            <!-- Image Grid -->
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; aspect-ratio: 16/9; background: var(--hover-bg); overflow: hidden;">
                                @foreach ($collection['images'] as $index => $image)
                                    <div style="background: url('{{ $image['url'] }}') center/cover; {{ count($collection['images']) == 1 ? 'grid-column: 1 / -1; grid-row: 1 / -1;' : '' }}">
                                    </div>
                                @endforeach
                                
                                @if (count($collection['images']) < 4)
                                    @for ($i = count($collection['images']); $i < 4; $i++)
                                        <div style="background: var(--hover-bg);"></div>
                                    @endfor
                                @endif
                            </div>
                            
                            <!-- Info -->
                            <div style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                    <span style="font-size: 1.5rem;">{{ $collection['icon'] }}</span>
                                    <h3 style="font-size: 1rem; font-weight: 500; color: #202124; margin: 0;">
                                        {{ $collection['name'] }}
                                    </h3>
                                </div>
                                <p style="font-size: 0.875rem; color: var(--secondary-color); margin: 0;">
                                    {{ $collection['count'] }} {{ Str::plural('photo', $collection['count']) }}
                                </p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Category Collections -->
    @if (!empty($collections))
        <div style="margin-bottom: 3rem;">
            <h2 style="font-size: 1.25rem; font-weight: 500; color: #202124; margin-bottom: 1rem;">
                <span class="material-symbols-outlined" style="font-size: 1.5rem; vertical-align: middle; margin-right: 0.5rem;">category</span>
                Categories
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                @foreach ($collections as $collection)
                    <a wire:navigate href="{{ route('gallery') }}?q={{ urlencode($collection['slug']) }}" 
                       class="collection-card"
                       style="text-decoration: none; color: inherit; display: block; transition: var(--transition);">
                        <div class="card" style="padding: 0; overflow: hidden; cursor: pointer; transition: var(--transition);"
                             onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='var(--shadow-md)'"
                             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            
                            <!-- Image Grid -->
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; aspect-ratio: 16/9; background: var(--hover-bg); overflow: hidden;">
                                @foreach ($collection['images'] as $index => $image)
                                    <div style="background: url('{{ $image['url'] }}') center/cover;">
                                    </div>
                                @endforeach
                                
                                @if (count($collection['images']) < 4)
                                    @for ($i = count($collection['images']); $i < 4; $i++)
                                        <div style="background: var(--hover-bg);"></div>
                                    @endfor
                                @endif
                            </div>
                            
                            <!-- Info -->
                            <div style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                    <span style="font-size: 1.5rem;">{{ $collection['icon'] }}</span>
                                    <h3 style="font-size: 1rem; font-weight: 500; color: #202124; margin: 0;">
                                        {{ $collection['name'] }}
                                    </h3>
                                </div>
                                <p style="font-size: 0.875rem; color: var(--secondary-color); margin: 0;">
                                    {{ $collection['count'] }} {{ Str::plural('photo', $collection['count']) }}
                                </p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Empty State -->
    @if (empty($collections) && empty($faceCollections))
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h2 class="empty-state-title">No collections yet</h2>
            <p class="empty-state-description">
                Collections will appear here once your photos are analyzed by AI
            </p>
            <a wire:navigate href="{{ route('gallery') }}" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">photo_library</span>
                View Gallery
            </a>
        </div>
    @endif
</div>

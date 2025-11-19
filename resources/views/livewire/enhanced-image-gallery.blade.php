<div x-data="{ showEditor: false }" x-on:keydown.escape.window="$wire.closeDetails(); $wire.exitSelectionMode()">
    <!-- Top Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.25rem;">
                @if ($showTrash)
                    <span class="material-symbols-outlined" style="font-size: 1.5rem; vertical-align: middle; margin-right: 0.5rem;">delete</span>
                    Trash
                @elseif ($showFavorites)
                    <span class="material-symbols-outlined" style="font-size: 1.5rem; vertical-align: middle; margin-right: 0.5rem; color: #f9ab00;">star</span>
                    Favorites
                @elseif ($searchQuery)
                    <span class="material-symbols-outlined" style="font-size: 1.5rem; vertical-align: middle; margin-right: 0.5rem;">search</span>
                    Search Results
                @else
                    Photos
                @endif
            </h1>
            <p style="font-size: 0.875rem; color: var(--secondary-color);">
                @if ($selectionMode && !empty($selectedIds))
                    {{ count($selectedIds) }} selected
                @elseif ($searchQuery)
                    {{ $searchResultsCount }} {{ Str::plural('result', $searchResultsCount) }} for "{{ Str::limit($searchQuery, 50) }}"
                @else
                    {{ count($images) }} {{ Str::plural('photo', count($images)) }}
                @endif
            </p>
        </div>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <!-- Clear Search Button -->
            @if ($searchQuery)
                <button wire:click="clearSearch" class="btn btn-secondary">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">close</span>
                    Clear Search
                </button>
            @endif
            
            <!-- Sort Dropdown -->
            @if (!$searchQuery)
                <select wire:model.live="sortBy" class="btn btn-secondary" style="padding: 0.5rem 1rem; cursor: pointer;">
                    <option value="date_taken">📅 Photo Date</option>
                    <option value="created_at">⬆️ Upload Date</option>
                    <option value="is_favorite">⭐ Favorites First</option>
                </select>
            @endif
            
            <!-- Selection Mode Toggle -->
            @if (!$showTrash)
                <button wire:click="{{ $selectionMode ? 'exitSelectionMode' : 'toggleSelectionMode' }}" class="btn {{ $selectionMode ? 'btn-primary' : 'btn-secondary' }}">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">{{ $selectionMode ? 'check_circle' : 'check_box_outline_blank' }}</span>
                    {{ $selectionMode ? 'Cancel' : 'Select' }}
                </button>
            @endif
            
            <!-- Favorites Filter -->
            <button wire:click="toggleFavorites" class="btn btn-secondary" title="Show favorites">
                <span class="material-symbols-outlined" style="font-size: 1.125rem; color: {{ $showFavorites ? '#f9ab00' : 'inherit' }};">{{ $showFavorites ? 'star' : 'star_outline' }}</span>
            </button>
            
            <!-- Trash -->
            <button wire:click="toggleTrash" class="btn btn-secondary" title="{{ $showTrash ? 'Hide trash' : 'Show trash' }}">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">{{ $showTrash ? 'folder' : 'delete' }}</span>
                @if ($stats['trashed'] > 0)
                    <span style="background: #d93025; color: white; padding: 0.125rem 0.5rem; border-radius: 12px; font-size: 0.75rem;">{{ $stats['trashed'] }}</span>
                @endif
            </button>
            
            <!-- Upload -->
            <a wire:navigate href="{{ route('instant-upload') }}" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">upload</span>
                Upload
            </a>
        </div>
    </div>

    <!-- Bulk Actions Toolbar (Selection Mode) -->
    @if ($selectionMode)
        <div class="card" style="margin-bottom: 1.5rem; padding: 1rem; background: #e8f0fe;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; gap: 0.5rem;">
                    <button wire:click="selectAll" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                        Select All
                    </button>
                    <button wire:click="deselectAll" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                        Deselect All
                    </button>
                </div>
                
                @if (!empty($selectedIds))
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button wire:click="bulkFavorite" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">star</span>
                            Favorite
                        </button>
                        <button wire:click="bulkDownload" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">download</span>
                            Download
                        </button>
                        <button wire:click="bulkDelete" class="btn" style="background: #d93025; color: white; font-size: 0.875rem; padding: 0.5rem 1rem;">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">delete</span>
                            Delete ({{ count($selectedIds) }})
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Filter Tag Badge -->
    @if ($filterTag)
        <div style="margin-bottom: 1.5rem;">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: #e8f0fe; border-radius: 24px;">
                <span style="font-size: 0.875rem; color: var(--primary-color);">
                    <strong>{{ $filterTag }}</strong>
                </span>
                <button wire:click="clearFilter" style="background: none; border: none; cursor: pointer; color: var(--primary-color); font-size: 1.25rem; padding: 0; display: flex; align-items: center;">
                    ×
                </button>
            </div>
        </div>
    @endif

    <!-- Loading Indicator -->
    <div wire:loading wire:target="search, performSearch" style="margin-bottom: 1.5rem;">
        <div class="card" style="padding: 2rem; text-align: center; background: #f8f9fa;">
            <div class="spinner" style="margin: 0 auto 1rem;"></div>
            <p style="color: var(--secondary-color);">Searching your photos...</p>
        </div>
    </div>

    @if (empty($images))
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon">
                @if ($searchQuery)
                    🔍
                @elseif ($showTrash)
                    🗑️
                @elseif ($showFavorites)
                    ⭐
                @else
                    📸
                @endif
            </div>
            <h2 class="empty-state-title">
                @if ($searchQuery)
                    No results found
                @elseif ($showTrash)
                    No items in trash
                @elseif ($showFavorites)
                    No favorite photos
                @else
                    No photos yet
                @endif
            </h2>
            <p class="empty-state-description">
                @if ($searchQuery)
                    No photos match "{{ $searchQuery }}". Try different keywords or upload more photos.
                @elseif ($showTrash)
                    Deleted photos will appear here
                @elseif ($showFavorites)
                    Star photos to see them here
                @else
                    Upload photos to get started
                @endif
            </p>
            @if ($searchQuery)
                <button wire:click="clearSearch" class="btn btn-secondary">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">close</span>
                    Clear search
                </button>
            @elseif (!$showTrash && !$showFavorites)
                <a wire:navigate href="{{ route('instant-upload') }}" class="btn btn-primary">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">upload</span>
                    Upload photos
                </a>
            @endif
        </div>
    @else
        <!-- Google Photos-style Masonry Grid -->
        <div class="photos-grid">
            @php $lastDate = null; @endphp
            
            @foreach ($images as $image)
                @php
                    // Use original photo date (date_taken) if available, otherwise upload date
                    $imageDate = $image['date_for_display'];
                @endphp
                
                @if ($imageDate !== $lastDate)
                    <div class="date-separator">{{ $imageDate }}</div>
                    @php $lastDate = $imageDate; @endphp
                @endif
                
                <div class="photo-item" 
                     wire:click="@if($selectionMode)toggleSelect({{ $image['id'] }})@else viewDetails({{ $image['id'] }})@endif"
                     style="position: relative; cursor: pointer; {{ $selectionMode && in_array($image['id'], $selectedIds) ? 'outline: 3px solid var(--primary-color); outline-offset: -3px;' : '' }}">
                    
                    <img src="{{ $image['url'] }}" alt="{{ $image['filename'] }}" loading="lazy" style="pointer-events: none;">
                    
                    <!-- Favorite Star (Top Right) -->
                    @if ($image['is_favorite'] && !$selectionMode)
                        <div style="position: absolute; top: 0.5rem; right: 0.5rem; width: 28px; height: 28px; background: rgba(0,0,0,0.6); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <span class="material-symbols-outlined" style="font-size: 1rem; color: #f9ab00;">star</span>
                        </div>
                    @endif
                    
                    <!-- Selection Checkbox (Top Left) -->
                    @if ($selectionMode)
                        <div style="position: absolute; top: 0.5rem; left: 0.5rem; width: 24px; height: 24px; background: white; border: 2px solid var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            @if (in_array($image['id'], $selectedIds))
                                <span class="material-symbols-outlined" style="font-size: 1.25rem; color: var(--primary-color);">check</span>
                            @endif
                        </div>
                    @endif
                    
                    <!-- Hover Overlay -->
                    <div class="photo-overlay" style="{{ $selectionMode ? 'opacity: 0 !important;' : '' }}">
                        @if (!empty($image['meta_tags']))
                            <div class="photo-overlay-title">
                                {{ implode(' · ', array_slice($image['meta_tags'], 0, 2)) }}
                            </div>
                        @endif
                        <div class="photo-overlay-meta">
                            @if ($image['face_count'] > 0)
                                <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">face</span>
                                {{ $image['face_count'] }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Enhanced Lightbox Modal -->
    @if ($selectedImage)
        <div class="lightbox" wire:click="closeDetails">
            <button class="lightbox-close" wire:click="closeDetails">
                ×
            </button>
            
            <div class="lightbox-content" wire:click.stop>
                <!-- Image Container -->
                <div class="lightbox-image-container">
                    <img src="{{ $selectedImage['url'] }}" alt="{{ $selectedImage['filename'] }}" class="lightbox-image" id="lightbox-image">
                    
                    <!-- Image Actions Overlay -->
                    <div style="position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; background: rgba(0,0,0,0.8); padding: 0.75rem; border-radius: 24px;">
                        <button wire:click.stop="toggleFavorite({{ $selectedImage['id'] }})" class="icon-btn" style="background: rgba(255,255,255,0.1); color: white;">
                            <span class="material-symbols-outlined" style="color: {{ $selectedImage['is_favorite'] ? '#f9ab00' : 'white' }};">{{ $selectedImage['is_favorite'] ? 'star' : 'star_outline' }}</span>
                        </button>
                        <button wire:click.stop="downloadImage({{ $selectedImage['id'] }})" class="icon-btn" style="background: rgba(255,255,255,0.1); color: white;">
                            <span class="material-symbols-outlined">download</span>
                        </button>
                        @if ($selectedImage['is_trashed'])
                            <button wire:click.stop="restoreImage({{ $selectedImage['id'] }})" class="icon-btn" style="background: rgba(76, 175, 80, 0.9); color: white;">
                                <span class="material-symbols-outlined">restore_from_trash</span>
                            </button>
                            <button wire:click.stop="permanentlyDelete({{ $selectedImage['id'] }})" class="icon-btn" style="background: rgba(211, 47, 47, 0.9); color: white;" onclick="return confirm('Permanently delete this image? This cannot be undone!')">
                                <span class="material-symbols-outlined">delete_forever</span>
                            </button>
                        @else
                            <button wire:click.stop="deleteImage({{ $selectedImage['id'] }})" class="icon-btn" style="background: rgba(211, 47, 47, 0.9); color: white;">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        @endif
                    </div>
                </div>
                
                <!-- Info Sidebar -->
                <div class="lightbox-sidebar">
                    <h2 style="font-size: 1.25rem; font-weight: 500; margin-bottom: 1.5rem; color: #202124;">
                        Info
                    </h2>
                    
                    <!-- Filename -->
                    <div style="margin-bottom: 1.5rem;">
                        <div style="font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            Filename
                        </div>
                        <div style="font-size: 0.875rem; color: #202124; word-break: break-all;">
                            {{ $selectedImage['filename'] }}
                        </div>
                    </div>

                    <!-- Description -->
                    @if ($selectedImage['description'])
                        <div style="margin-bottom: 1.5rem;">
                            <div style="font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                Description
                            </div>
                            <div style="font-size: 0.875rem; color: #202124; line-height: 1.6;">
                                {{ $selectedImage['description'] }}
                            </div>
                        </div>
                    @endif

                    <!-- Detailed Description -->
                    @if ($selectedImage['detailed_description'] && $selectedImage['detailed_description'] !== $selectedImage['description'])
                        <div style="margin-bottom: 1.5rem;">
                            <div style="font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                Detailed Analysis
                            </div>
                            <div style="font-size: 0.875rem; color: #202124; line-height: 1.6;">
                                {{ $selectedImage['detailed_description'] }}
                            </div>
                        </div>
                    @endif

                    <!-- Tags -->
                    @if (!empty($selectedImage['meta_tags']))
                        <div style="margin-bottom: 1.5rem;">
                            <div style="font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                Tags
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                @foreach ($selectedImage['meta_tags'] as $tag)
                                    <span class="tag" wire:click.stop="filterByTag('{{ $tag }}')" style="cursor: pointer;">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Camera & Exposure Info -->
                    @if ($selectedImage['camera_make'] || $selectedImage['camera_model'] || $selectedImage['exposure_time'])
                        <div style="padding-top: 1.5rem; border-top: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                            <div style="font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle; margin-right: 0.25rem;">photo_camera</span>
                                Camera Info
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                @if ($selectedImage['camera_make'] || $selectedImage['camera_model'])
                                    <div>
                                        <div style="font-size: 0.75rem; color: var(--secondary-color);">Camera</div>
                                        <div style="font-size: 0.875rem; color: #202124;">
                                            {{ $selectedImage['camera_make'] }} {{ $selectedImage['camera_model'] }}
                                        </div>
                                    </div>
                                @endif
                                @if ($selectedImage['lens_model'])
                                    <div>
                                        <div style="font-size: 0.75rem; color: var(--secondary-color);">Lens</div>
                                        <div style="font-size: 0.875rem; color: #202124;">{{ $selectedImage['lens_model'] }}</div>
                                    </div>
                                @endif
                                @if ($selectedImage['date_taken'])
                                    <div>
                                        <div style="font-size: 0.75rem; color: var(--secondary-color);">Date Taken</div>
                                        <div style="font-size: 0.875rem; color: #202124;">{{ $selectedImage['date_taken'] }}</div>
                                    </div>
                                @endif
                                @if ($selectedImage['exposure_time'] || $selectedImage['f_number'] || $selectedImage['iso'] || $selectedImage['focal_length'])
                                    <div>
                                        <div style="font-size: 0.75rem; color: var(--secondary-color);">Exposure</div>
                                        <div style="font-size: 0.875rem; color: #202124;">
                                            @if ($selectedImage['exposure_time']){{ $selectedImage['exposure_time'] }}@endif
                                            @if ($selectedImage['f_number']){{ $selectedImage['exposure_time'] ? ' · ' : '' }}{{ $selectedImage['f_number'] }}@endif
                                            @if ($selectedImage['iso']){{ ($selectedImage['exposure_time'] || $selectedImage['f_number']) ? ' · ' : '' }}{{ $selectedImage['iso'] }}@endif
                                            @if ($selectedImage['focal_length']){{ ($selectedImage['exposure_time'] || $selectedImage['f_number'] || $selectedImage['iso']) ? ' · ' : '' }}{{ $selectedImage['focal_length'] }}@endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- File Details -->
                    <div style="padding-top: 1.5rem; border-top: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                        <div style="font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle; margin-right: 0.25rem;">description</span>
                            File Details
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            @if ($selectedImage['dimensions'])
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--secondary-color);">Dimensions</div>
                                    <div style="font-size: 0.875rem; color: #202124;">{{ $selectedImage['dimensions'] }} px</div>
                                </div>
                            @endif
                            @if ($selectedImage['file_size'])
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--secondary-color);">File Size</div>
                                    <div style="font-size: 0.875rem; color: #202124;">{{ $selectedImage['file_size'] }}</div>
                                </div>
                            @endif
                            @if ($selectedImage['mime_type'])
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--secondary-color);">Type</div>
                                    <div style="font-size: 0.875rem; color: #202124;">{{ strtoupper(str_replace('image/', '', $selectedImage['mime_type'])) }}</div>
                                </div>
                            @endif
                            <div>
                                <div style="font-size: 0.75rem; color: var(--secondary-color);">Uploaded</div>
                                <div style="font-size: 0.875rem; color: #202124;">{{ $selectedImage['created_at'] }}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--secondary-color);">Views</div>
                                <div style="font-size: 0.875rem; color: #202124;">{{ $selectedImage['view_count'] }} {{ Str::plural('view', $selectedImage['view_count']) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Analysis -->
                    @if ($selectedImage['face_count'] > 0)
                        <div style="padding-top: 1.5rem; border-top: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                            <div style="font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle; margin-right: 0.25rem;">face</span>
                                AI Analysis
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--secondary-color);">Faces Detected</div>
                                <div style="font-size: 0.875rem; color: #202124;">{{ $selectedImage['face_count'] }} {{ Str::plural('face', $selectedImage['face_count']) }}</div>
                            </div>
                        </div>
                    @endif

                    <!-- GPS Location -->
                    @if ($selectedImage['has_gps'])
                        <div style="padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                            <div style="font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle; margin-right: 0.25rem;">location_on</span>
                                Location
                            </div>
                            <div>
                                <div style="font-size: 0.875rem; color: #202124; margin-bottom: 0.5rem;">
                                    {{ number_format($selectedImage['gps_latitude'], 6) }}, {{ number_format($selectedImage['gps_longitude'], 6) }}
                                </div>
                                <a href="https://www.google.com/maps?q={{ $selectedImage['gps_latitude'] }},{{ $selectedImage['gps_longitude'] }}" 
                                   target="_blank" 
                                   class="btn btn-secondary" 
                                   style="width: 100%; justify-content: center; font-size: 0.875rem; padding: 0.5rem 1rem;">
                                    <span class="material-symbols-outlined" style="font-size: 1rem;">map</span>
                                    View on Map
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<script>
// Download functionality
document.addEventListener('livewire:initialized', () => {
    Livewire.on('download-image', (event) => {
        const link = document.createElement('a');
        link.href = event.url;
        link.download = event.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    Livewire.on('download-multiple', (event) => {
        event.urls.forEach((url, index) => {
            setTimeout(() => {
                const link = document.createElement('a');
                link.href = url;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }, index * 500);
        });
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Escape to close/deselect
    if (e.key === 'Escape') {
        // Close details popup if open
        if (@this.selectedImage) {
            @this.closeDetails();
        } 
        // Exit selection mode if active
        else if (@this.selectionMode) {
            @this.exitSelectionMode();
        }
    }
    
    // Delete key
    if (e.key === 'Delete' && @this.selectedIds.length > 0) {
        if (confirm('Delete selected images?')) {
            @this.bulkDelete();
        }
    }
    
    // Ctrl/Cmd + A to select all
    if ((e.ctrlKey || e.metaKey) && e.key === 'a' && @this.selectionMode) {
        e.preventDefault();
        @this.selectAll();
    }
});
</script>


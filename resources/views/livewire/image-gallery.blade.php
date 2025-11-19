<div>
    <!-- Gallery Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 500; color: #202124; margin-bottom: 0.25rem;">
                Photos
            </h1>
            <p style="font-size: 0.875rem; color: var(--secondary-color);">
                {{ count($images) }} {{ Str::plural('photo', count($images)) }}
            </p>
        </div>
        
        <div style="display: flex; gap: 0.5rem;">
            @if ($filterTag)
                <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: #e8f0fe; border-radius: 24px;">
                    <span style="font-size: 0.875rem; color: var(--primary-color);">
                        <strong>{{ $filterTag }}</strong>
                    </span>
                    <button wire:click="clearFilter" style="background: none; border: none; cursor: pointer; color: var(--primary-color); font-size: 1.25rem; padding: 0; display: flex; align-items: center;">
                        ×
                    </button>
                </div>
            @endif
            <a wire:navigate href="{{ route('instant-upload') }}" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">upload</span>
                Upload
            </a>
        </div>
    </div>

    @if (empty($images))
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon">📸</div>
            <h2 class="empty-state-title">No photos yet</h2>
            <p class="empty-state-description">Upload photos to see them here</p>
            <a wire:navigate href="{{ route('instant-upload') }}" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">upload</span>
                Upload photos
            </a>
        </div>
    @else
        <!-- Google Photos-style Masonry Grid -->
        <div class="photos-grid">
            @php
                $lastDate = null;
            @endphp
            
            @foreach ($images as $image)
                @php
                    $imageDate = \Carbon\Carbon::parse($image['created_at'])->format('F d, Y');
                @endphp
                
                @if ($imageDate !== $lastDate)
                    <div class="date-separator">{{ $imageDate }}</div>
                    @php $lastDate = $imageDate; @endphp
                @endif
                
                <div class="photo-item" wire:click="viewDetails({{ $image['id'] }})">
                    <img src="{{ $image['url'] }}" alt="{{ $image['filename'] }}" loading="lazy">
                    
                    <!-- Hover Overlay -->
                    <div class="photo-overlay">
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
                    
                    <!-- Selection Checkbox -->
                    <div class="photo-checkbox"></div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Lightbox Modal -->
    @if ($selectedImage)
        <div class="lightbox" wire:click="closeDetails">
            <button class="lightbox-close" wire:click="closeDetails">
                ×
            </button>
            
            <div class="lightbox-content" wire:click.stop>
                <!-- Image Container -->
                <div class="lightbox-image-container">
                    <img src="{{ $selectedImage['url'] }}" alt="{{ $selectedImage['filename'] }}" class="lightbox-image">
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
                    <div style="margin-bottom: 1.5rem;">
                        <div style="font-size: 0.75rem; color: var(--secondary-color); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            Description
                        </div>
                        <div style="font-size: 0.875rem; color: #202124; line-height: 1.6;">
                            {{ $selectedImage['description'] }}
                        </div>
                    </div>

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
                                    <span class="tag" wire:click="filterByTag('{{ $tag }}')">
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
                                            @if ($selectedImage['camera_make'])
                                                {{ $selectedImage['camera_make'] }}
                                            @endif
                                            @if ($selectedImage['camera_model'])
                                                {{ $selectedImage['camera_model'] }}
                                            @endif
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


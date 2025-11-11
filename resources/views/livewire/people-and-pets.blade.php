<div style="padding: 2rem; max-width: 1600px; margin: 0 auto;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                People & Pets
            </h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                {{ count($clusters) }} clusters found • Click to view photos
            </p>
        </div>
        
        <div style="display: flex; gap: 1rem; align-items: center;">
            <!-- Search -->
            <input 
                type="text" 
                wire:model.live="searchQuery"
                placeholder="Search by name..."
                style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid #e5e7eb; min-width: 200px;">
            
            <!-- Type Filter -->
            <select wire:model.live="selectedType" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                <option value="all">All Types</option>
                <option value="person">People</option>
                <option value="pet">Pets</option>
                <option value="unknown">Unknown</option>
            </select>
            
            <!-- Recluster Button -->
            <button wire:click="reclusterAll" class="btn btn-secondary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">refresh</span>
                Re-cluster
            </button>
        </div>
    </div>

    @if(session()->has('message'))
        <div style="padding: 1rem; background: #10b98114; color: #10b981; border-radius: 8px; margin-bottom: 1rem;">
            {{ session('message') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div style="padding: 1rem; background: #ef444414; color: #ef4444; border-radius: 8px; margin-bottom: 1rem;">
            {{ session('error') }}
        </div>
    @endif

    <!-- Clusters Grid -->
    @if(count($clusters) > 0)
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem;">
            @foreach($clusters as $cluster)
                <div style="background: var(--card-background); border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-sm); transition: transform 0.2s; cursor: pointer;"
                     onmouseover="this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.transform='translateY(0)'">
                    
                    <!-- Thumbnail -->
                    <div wire:click="viewCluster({{ $cluster['id'] }})" style="aspect-ratio: 1; overflow: hidden; background: #f3f4f6; position: relative;">
                        <img src="{{ $cluster['thumbnail_url'] }}" 
                             alt="{{ $cluster['name'] }}"
                             style="width: 100%; height: 100%; object-fit: cover;">
                        
                        <!-- Type Badge -->
                        <div style="position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(0, 0, 0, 0.7); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                            @if($cluster['type'] === 'person')
                                👤 Person
                            @elseif($cluster['type'] === 'pet')
                                🐾 Pet
                            @else
                                ❓ Unknown
                            @endif
                        </div>
                    </div>
                    
                    <!-- Info -->
                    <div style="padding: 1rem;">
                        <!-- Name (Editable) -->
                        @if($editingClusterId === $cluster['id'])
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <input 
                                    type="text" 
                                    wire:model="editingName"
                                    autofocus
                                    style="flex: 1; padding: 0.25rem 0.5rem; border: 1px solid var(--primary-color); border-radius: 4px; font-weight: 600;">
                                <button wire:click="saveClusterName" style="padding: 0.25rem 0.5rem; background: var(--primary-color); color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    ✓
                                </button>
                                <button wire:click="cancelEditing" style="padding: 0.25rem 0.5rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    ×
                                </button>
                            </div>
                        @else
                            <div wire:click="startEditing({{ $cluster['id'] }}, '{{ $cluster['name'] }}')" 
                                 style="font-weight: 600; font-size: 1rem; color: var(--text-primary); margin-bottom: 0.5rem; cursor: pointer; padding: 0.25rem 0; border-bottom: 2px solid transparent;"
                                 onmouseover="this.style.borderColor='var(--primary-color)'"
                                 onmouseout="this.style.borderColor='transparent'">
                                {{ $cluster['name'] }}
                            </div>
                        @endif
                        
                        <!-- Photo Count -->
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">photo_library</span>
                            <span>{{ $cluster['photo_count'] }} {{ $cluster['photo_count'] === 1 ? 'photo' : 'photos' }}</span>
                        </div>
                        
                        <!-- Actions -->
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                            <button wire:click="viewCluster({{ $cluster['id'] }})" style="flex: 1; padding: 0.5rem; background: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                                <span class="material-symbols-outlined" style="font-size: 1rem;">visibility</span>
                                View
                            </button>
                            
                            <!-- Type Dropdown -->
                            <div class="dropdown" style="position: relative;">
                                <button onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block'" 
                                        style="padding: 0.5rem; background: #f3f4f6; border: none; border-radius: 6px; cursor: pointer;">
                                    <span class="material-symbols-outlined" style="font-size: 1rem;">more_vert</span>
                                </button>
                                <div style="display: none; position: absolute; right: 0; top: 100%; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10; min-width: 150px; margin-top: 0.25rem;">
                                    <button wire:click="setType({{ $cluster['id'] }}, 'person')" style="width: 100%; text-align: left; padding: 0.75rem 1rem; border: none; background: none; cursor: pointer; font-size: 0.875rem;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                                        👤 Mark as Person
                                    </button>
                                    <button wire:click="setType({{ $cluster['id'] }}, 'pet')" style="width: 100%; text-align: left; padding: 0.75rem 1rem; border: none; background: none; cursor: pointer; font-size: 0.875rem;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                                        🐾 Mark as Pet
                                    </button>
                                    <button wire:click="deleteCluster({{ $cluster['id'] }})" style="width: 100%; text-align: left; padding: 0.75rem 1rem; border: none; background: none; cursor: pointer; color: #ef4444; font-size: 0.875rem;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='none'">
                                        🗑️ Delete Cluster
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div style="text-align: center; padding: 4rem 2rem;">
            <span class="material-symbols-outlined" style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 1rem;">
                groups
            </span>
            <h2 style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                No Faces Detected Yet
            </h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                Upload photos with people or pets to see them organized here!
            </p>
            <a href="{{ route('upload') }}" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 1.125rem;">add_photo_alternate</span>
                Upload Photos
            </a>
        </div>
    @endif
</div>

<!-- Close dropdowns when clicking outside -->
<script>
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown > div').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });
</script>

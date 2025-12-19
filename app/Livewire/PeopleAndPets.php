<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\FaceCluster;
use App\Services\FaceClusteringService;

class PeopleAndPets extends Component
{
    public $clusters = [];
    public $faceGroups = [];
    public $stats = [
        'total_faces' => 0,
    ];
    public $searchQuery = '';
    public $selectedType = 'all'; // all, person, pet, unknown
    public $editingClusterId = null;
    public $editingName = '';
    
    public function mount()
    {
        $this->loadClusters();
        $this->loadStats();
    }
    
    public function loadClusters()
    {
        $query = FaceCluster::where('photo_count', '>', 0);
        
        // Filter by type
        if ($this->selectedType !== 'all') {
            $query->where('type', $this->selectedType);
        }
        
        // Search filter
        if ($this->searchQuery) {
            $query->where('name', 'like', '%' . $this->searchQuery . '%');
        }
        
        $clusters = $query->orderBy('photo_count', 'desc')->get();
        
        $this->clusters = $clusters->map(function ($cluster) {
            return [
                'id' => $cluster->id,
                'name' => $cluster->name ?? 'Unknown ' . ucfirst($cluster->type),
                'type' => $cluster->type,
                'photo_count' => $cluster->photo_count,
                'thumbnail_url' => $cluster->thumbnail_url,
            ];
        })->toArray();
        
        // Also prepare faceGroups format for the view
        $this->faceGroups = $clusters->map(function ($cluster) {
            return [
                'id' => $cluster->id,
                'name' => $cluster->name ?? 'Unknown Person',
                'count' => $cluster->photo_count,
                'sample_image' => $cluster->thumbnail_url,
                'icon' => '👤',
            ];
        })->toArray();
    }
    
    public function loadStats()
    {
        $this->stats = [
            'total_faces' => FaceCluster::where('photo_count', '>', 0)->count(),
        ];
    }
    
    public function startEditing($clusterId, $currentName)
    {
        $this->editingClusterId = $clusterId;
        $this->editingName = $currentName;
    }
    
    public function saveClusterName()
    {
        if ($this->editingClusterId) {
            $cluster = FaceCluster::find($this->editingClusterId);
            if ($cluster) {
                $cluster->name = $this->editingName ?: null;
                $cluster->save();
            }
        }
        
        $this->editingClusterId = null;
        $this->editingName = '';
        $this->loadClusters();
    }
    
    public function cancelEditing()
    {
        $this->editingClusterId = null;
        $this->editingName = '';
    }
    
    public function viewCluster($clusterId)
    {
        return redirect()->route('gallery', ['face_cluster' => $clusterId]);
    }
    
    public function setType($clusterId, $type)
    {
        $cluster = FaceCluster::find($clusterId);
        if ($cluster) {
            $cluster->type = $type;
            $cluster->save();
            $this->loadClusters();
        }
    }
    
    public function deleteCluster($clusterId)
    {
        $cluster = FaceCluster::find($clusterId);
        if ($cluster) {
            // Remove cluster assignment from faces but keep faces
            $cluster->detectedFaces()->update(['face_cluster_id' => null]);
            $cluster->delete();
            $this->loadClusters();
        }
    }
    
    public function reclusterAll()
    {
        try {
            $service = app(FaceClusteringService::class);
            $service->reclusterAllFaces();
            $this->loadClusters();
            $this->loadStats();
            session()->flash('message', 'Faces re-clustered successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Re-clustering failed: ' . $e->getMessage());
        }
    }
    
    public function updatedSearchQuery()
    {
        $this->loadClusters();
        $this->loadStats();
    }
    
    public function updatedSelectedType()
    {
        $this->loadClusters();
        $this->loadStats();
    }
    
    public function render()
    {
        return view('livewire.people-and-pets')->layout('layouts.app');
    }
}

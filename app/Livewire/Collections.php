<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ImageFile;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Collections extends Component
{
    /**
     * Repository instance.
     */
    protected ImageRepository $imageRepository;
    
    /**
     * Boot the component.
     */
    public function boot(ImageRepository $imageRepository)
    {
        $this->imageRepository = $imageRepository;
    }

    public $collections = [];
    public $faceCollections = [];
    public $stats = [
        'total_images' => 0,
        'total_categories' => 0,
        'total_faces' => 0,
    ];

    /**
     * Category icons mapping.
     */
    const CATEGORY_ICONS = [
        'people' => '👥',
        'person' => '👤',
        'nature' => '🌿',
        'landscape' => '🏞️',
        'city' => '🏙️',
        'building' => '🏢',
        'architecture' => '🏛️',
        'car' => '🚗',
        'vehicle' => '🚙',
        'food' => '🍽️',
        'animal' => '🐾',
        'sunset' => '🌅',
        'beach' => '🏖️',
        'mountain' => '⛰️',
        'sky' => '☁️',
        'water' => '💧',
        'indoor' => '🏠',
        'outdoor' => '🌳',
        'night' => '🌙',
        'technology' => '💻',
        'phone' => '📱',
        'computer' => '🖥️',
        'art' => '🎨',
        'sport' => '⚽',
        'flower' => '🌸',
        'tree' => '🌲',
        'garden' => '🌺',
        'street' => '🛣️',
        'road' => '🛤️',
        'bridge' => '🌉',
        'house' => '🏡',
        'window' => '🪟',
        'door' => '🚪',
        'furniture' => '🪑',
        'fashion' => '👗',
        'clothing' => '👔',
        'accessories' => '👜',
    ];

    public function mount()
    {
        $this->loadCollections();
        $this->loadFaceCollections();
        $this->loadStats();
    }

    /**
     * Load AI-based category collections.
     */
    public function loadCollections()
    {
        try {
            // Get all images with meta_tags
            $images = ImageFile::whereNull('deleted_at')
                ->where('processing_status', 'completed')
                ->whereNotNull('meta_tags')
                ->select('id', 'file_path', 'meta_tags', 'description')
                ->get();

            // Group by categories
            $categoryGroups = [];
            
            foreach ($images as $image) {
                $tags = is_array($image->meta_tags) ? $image->meta_tags : [];
                
                foreach ($tags as $tag) {
                    $category = strtolower(trim($tag));
                    
                    if (!isset($categoryGroups[$category])) {
                        $categoryGroups[$category] = [
                            'name' => ucfirst($category),
                            'slug' => $category,
                            'icon' => $this->getCategoryIcon($category),
                            'count' => 0,
                            'images' => []
                        ];
                    }
                    
                    $categoryGroups[$category]['count']++;
                    
                    // Add image (limit to 4 thumbnails per category)
                    if (count($categoryGroups[$category]['images']) < 4) {
                        $categoryGroups[$category]['images'][] = [
                            'id' => $image->id,
                            'url' => asset('storage/' . str_replace('public/', '', $image->file_path))
                        ];
                    }
                }
            }

            // Sort by count (most popular first) and filter out small categories
            $this->collections = collect($categoryGroups)
                ->filter(fn($cat) => $cat['count'] >= 2) // At least 2 images
                ->sortByDesc('count')
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to load collections', ['error' => $e->getMessage()]);
            $this->collections = [];
        }
    }

    /**
     * Load face-based collections.
     */
    public function loadFaceCollections()
    {
        try {
            // Get images with faces
            $imagesWithFaces = ImageFile::whereNull('deleted_at')
                ->where('processing_status', 'completed')
                ->where('face_count', '>', 0)
                ->select('id', 'file_path', 'face_count', 'face_encodings')
                ->get();

            // Group by face count
            $faceGroups = [
                'single' => [
                    'name' => 'Single Person',
                    'icon' => '👤',
                    'count' => 0,
                    'images' => []
                ],
                'couple' => [
                    'name' => '2 People',
                    'icon' => '👥',
                    'count' => 0,
                    'images' => []
                ],
                'group' => [
                    'name' => 'Groups (3+)',
                    'icon' => '👨‍👩‍👧‍👦',
                    'count' => 0,
                    'images' => []
                ]
            ];

            foreach ($imagesWithFaces as $image) {
                $groupKey = 'group';
                
                if ($image->face_count == 1) {
                    $groupKey = 'single';
                } elseif ($image->face_count == 2) {
                    $groupKey = 'couple';
                }

                $faceGroups[$groupKey]['count']++;
                
                // Add image (limit to 4 thumbnails)
                if (count($faceGroups[$groupKey]['images']) < 4) {
                    $faceGroups[$groupKey]['images'][] = [
                        'id' => $image->id,
                        'url' => asset('storage/' . str_replace('public/', '', $image->file_path))
                    ];
                }
            }

            // Filter out empty groups
            $this->faceCollections = collect($faceGroups)
                ->filter(fn($group) => $group['count'] > 0)
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to load face collections', ['error' => $e->getMessage()]);
            $this->faceCollections = [];
        }
    }

    /**
     * Load statistics.
     */
    public function loadStats()
    {
        $stats = $this->imageRepository->getStatistics();
        
        $this->stats = [
            'total_images' => $stats['total'],
            'total_categories' => count($this->collections),
            'total_faces' => ImageFile::whereNull('deleted_at')
                ->where('face_count', '>', 0)
                ->count(),
        ];
    }

    /**
     * Get category icon.
     */
    protected function getCategoryIcon(string $category): string
    {
        $category = strtolower($category);
        
        // Check for exact match
        if (isset(self::CATEGORY_ICONS[$category])) {
            return self::CATEGORY_ICONS[$category];
        }
        
        // Check for partial match
        foreach (self::CATEGORY_ICONS as $key => $icon) {
            if (str_contains($category, $key) || str_contains($key, $category)) {
                return $icon;
            }
        }
        
        // Default icon
        return '📁';
    }

    /**
     * View category.
     */
    public function viewCategory(string $category)
    {
        return redirect()->route('gallery', ['filter' => $category]);
    }

    /**
     * View face group.
     */
    public function viewFaceGroup(string $type)
    {
        // Redirect to gallery with face filter
        return redirect()->route('gallery', ['faces' => $type]);
    }

    public function render()
    {
        return view('livewire.collections')
            ->layout('layouts.app');
    }
}

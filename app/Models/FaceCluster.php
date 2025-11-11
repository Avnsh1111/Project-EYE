<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaceCluster extends Model
{
    protected $fillable = [
        'name',
        'type',
        'representative_encoding',
        'thumbnail_path',
        'photo_count',
    ];

    protected $casts = [
        'representative_encoding' => 'array',
        'photo_count' => 'integer',
    ];

    /**
     * Get all detected faces in this cluster
     */
    public function detectedFaces(): HasMany
    {
        return $this->hasMany(DetectedFace::class);
    }

    /**
     * Get all images that contain this person/pet
     */
    public function images()
    {
        return ImageFile::whereIn('id', function ($query) {
            $query->select('image_file_id')
                ->from('detected_faces')
                ->where('face_cluster_id', $this->id);
        })->distinct();
    }

    /**
     * Get the best thumbnail for this cluster
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail_path) {
            return asset('storage/' . str_replace('public/', '', $this->thumbnail_path));
        }

        // Get first face's image as fallback
        $firstFace = $this->detectedFaces()->with('imageFile')->first();
        if ($firstFace && $firstFace->imageFile) {
            return asset('storage/' . str_replace('public/', '', $firstFace->imageFile->file_path));
        }

        return asset('images/default-avatar.png');
    }

    /**
     * Update photo count
     */
    public function updatePhotoCount()
    {
        $this->photo_count = $this->detectedFaces()
            ->distinct('image_file_id')
            ->count('image_file_id');
        $this->save();
    }

    /**
     * Calculate representative encoding (average of all faces)
     */
    public function updateRepresentativeEncoding()
    {
        $faces = $this->detectedFaces()->get();

        if ($faces->isEmpty()) {
            return;
        }

        $encodings = $faces->map(fn($face) => $face->face_encoding)->toArray();
        
        // Calculate average encoding
        $avgEncoding = [];
        $dimension = count($encodings[0]);
        
        for ($i = 0; $i < $dimension; $i++) {
            $sum = 0;
            foreach ($encodings as $encoding) {
                $sum += $encoding[$i];
            }
            $avgEncoding[] = $sum / count($encodings);
        }

        $this->representative_encoding = $avgEncoding;
        $this->save();
    }
}

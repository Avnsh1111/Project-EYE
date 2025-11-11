<?php

namespace App\Services;

use App\Models\FaceCluster;
use App\Models\DetectedFace;
use App\Models\ImageFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FaceClusteringService
{
    // Similarity threshold for considering two faces as the same person
    // Higher = stricter (0.7 = very strict, 0.5 = more lenient)
    protected float $similarityThreshold = 0.6;

    /**
     * Process detected faces from an image analysis
     */
    public function processFaces(ImageFile $imageFile, array $facesData): void
    {
        if (empty($facesData)) {
            return;
        }

        foreach ($facesData as $faceData) {
            $this->processSingleFace($imageFile, $faceData);
        }
    }

    /**
     * Process a single detected face
     */
    protected function processSingleFace(ImageFile $imageFile, array $faceData): ?DetectedFace
    {
        // Create detected face record
        $detectedFace = DetectedFace::create([
            'image_file_id' => $imageFile->id,
            'face_encoding' => $faceData['encoding'],
            'face_location' => $faceData['location'] ?? null,
            'confidence' => $faceData['confidence'] ?? 1.0,
        ]);

        // Find or create cluster for this face
        $cluster = $this->findOrCreateCluster($detectedFace);

        // Assign face to cluster
        $detectedFace->face_cluster_id = $cluster->id;
        $detectedFace->save();

        // Update cluster statistics
        $cluster->updatePhotoCount();
        $cluster->updateRepresentativeEncoding();

        // Update thumbnail if this is a good quality face
        if (!$cluster->thumbnail_path || ($faceData['confidence'] ?? 1.0) > 0.95) {
            $cluster->thumbnail_path = $imageFile->file_path;
            $cluster->save();
        }

        return $detectedFace;
    }

    /**
     * Find an existing cluster or create a new one
     */
    protected function findOrCreateCluster(DetectedFace $newFace): FaceCluster
    {
        // Get all existing clusters
        $clusters = FaceCluster::all();

        $bestMatch = null;
        $bestSimilarity = 0;

        // Find the best matching cluster
        foreach ($clusters as $cluster) {
            if (!$cluster->representative_encoding) {
                continue;
            }

            $similarity = $this->calculateSimilarity(
                $newFace->face_encoding,
                $cluster->representative_encoding
            );

            if ($similarity > $bestSimilarity && $similarity >= $this->similarityThreshold) {
                $bestSimilarity = $similarity;
                $bestMatch = $cluster;
            }
        }

        // If found a good match, use it
        if ($bestMatch) {
            Log::info("Face matched to existing cluster", [
                'cluster_id' => $bestMatch->id,
                'cluster_name' => $bestMatch->name,
                'similarity' => $bestSimilarity
            ]);
            return $bestMatch;
        }

        // No match found, create new cluster
        $newCluster = FaceCluster::create([
            'name' => null, // User will name it later
            'type' => 'person',
            'representative_encoding' => $newFace->face_encoding,
            'photo_count' => 0,
        ]);

        Log::info("Created new face cluster", [
            'cluster_id' => $newCluster->id
        ]);

        return $newCluster;
    }

    /**
     * Calculate cosine similarity between two face encodings
     */
    protected function calculateSimilarity(array $encoding1, array $encoding2): float
    {
        if (empty($encoding1) || empty($encoding2)) {
            return 0.0;
        }

        // Calculate cosine similarity
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        $length = min(count($encoding1), count($encoding2));

        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $encoding1[$i] * $encoding2[$i];
            $magnitude1 += $encoding1[$i] ** 2;
            $magnitude2 += $encoding2[$i] ** 2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Re-cluster all faces (useful after threshold changes)
     */
    public function reclusterAllFaces(): void
    {
        Log::info("Starting face re-clustering...");

        DB::beginTransaction();

        try {
            // Reset all clusters
            DetectedFace::query()->update(['face_cluster_id' => null]);
            FaceCluster::query()->delete();

            // Reprocess all faces
            DetectedFace::with('imageFile')
                ->chunk(100, function ($faces) {
                    foreach ($faces as $face) {
                        if ($face->imageFile) {
                            $cluster = $this->findOrCreateCluster($face);
                            $face->face_cluster_id = $cluster->id;
                            $face->save();
                        }
                    }
                });

            // Update all cluster statistics
            FaceCluster::all()->each(function ($cluster) {
                $cluster->updatePhotoCount();
                $cluster->updateRepresentativeEncoding();
            });

            DB::commit();

            Log::info("Face re-clustering completed", [
                'total_clusters' => FaceCluster::count()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Face re-clustering failed", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Merge two clusters
     */
    public function mergeClusters(FaceCluster $cluster1, FaceCluster $cluster2): FaceCluster
    {
        // Move all faces from cluster2 to cluster1
        DetectedFace::where('face_cluster_id', $cluster2->id)
            ->update(['face_cluster_id' => $cluster1->id]);

        // Keep the named cluster or the one with more photos
        if (!$cluster1->name && $cluster2->name) {
            $cluster1->name = $cluster2->name;
        }

        // Update cluster1 statistics
        $cluster1->updatePhotoCount();
        $cluster1->updateRepresentativeEncoding();

        // Delete cluster2
        $cluster2->delete();

        return $cluster1;
    }

    /**
     * Set clustering threshold
     */
    public function setThreshold(float $threshold): void
    {
        $this->similarityThreshold = max(0.4, min(0.8, $threshold));
    }
}


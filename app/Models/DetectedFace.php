<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetectedFace extends Model
{
    protected $fillable = [
        'image_file_id',
        'face_cluster_id',
        'face_encoding',
        'face_location',
        'confidence',
    ];

    protected $casts = [
        'face_encoding' => 'array',
        'face_location' => 'array',
        'confidence' => 'float',
    ];

    /**
     * Get the image this face belongs to
     */
    public function imageFile(): BelongsTo
    {
        return $this->belongsTo(ImageFile::class);
    }

    /**
     * Get the cluster this face belongs to
     */
    public function faceCluster(): BelongsTo
    {
        return $this->belongsTo(FaceCluster::class);
    }

    /**
     * Calculate similarity with another face (cosine similarity)
     */
    public function similarityWith($otherEncoding): float
    {
        if (!is_array($otherEncoding) || empty($otherEncoding)) {
            return 0.0;
        }

        $encoding1 = $this->face_encoding;
        $encoding2 = $otherEncoding;

        // Calculate cosine similarity
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($encoding1); $i++) {
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
     * Calculate face distance (Euclidean distance)
     */
    public function distanceTo($otherEncoding): float
    {
        if (!is_array($otherEncoding) || empty($otherEncoding)) {
            return PHP_FLOAT_MAX;
        }

        $encoding1 = $this->face_encoding;
        $encoding2 = $otherEncoding;

        $sum = 0;
        for ($i = 0; $i < count($encoding1); $i++) {
            $diff = $encoding1[$i] - $encoding2[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }
}

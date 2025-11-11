<?php

namespace Database\Factories;

use App\Models\ImageFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFileFactory extends Factory
{
    protected $model = ImageFile::class;

    public function definition(): array
    {
        return [
            'file_path' => 'public/images/' . $this->faker->uuid . '.jpg',
            'original_filename' => $this->faker->word() . '.jpg',
            'description' => $this->faker->sentence(),
            'detailed_description' => $this->faker->paragraph(),
            'meta_tags' => $this->faker->randomElements(['nature', 'portrait', 'landscape', 'urban', 'wildlife'], 3),
            'face_count' => $this->faker->numberBetween(0, 5),
            'face_encodings' => [],
            'embedding' => array_fill(0, 512, $this->faker->randomFloat(4, -1, 1)),
            
            // File metadata
            'mime_type' => 'image/jpeg',
            'file_size' => $this->faker->numberBetween(100000, 5000000),
            'width' => $this->faker->numberBetween(800, 4000),
            'height' => $this->faker->numberBetween(600, 3000),
            'exif_data' => [],
            
            // Camera info
            'camera_make' => $this->faker->randomElement(['Canon', 'Nikon', 'Sony', 'Fujifilm', null]),
            'camera_model' => $this->faker->randomElement(['EOS R5', 'Z9', 'A7IV', 'X-T5', null]),
            'lens_model' => $this->faker->randomElement(['24-70mm F2.8', '70-200mm F4', '50mm F1.8', null]),
            'date_taken' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'exposure_time' => $this->faker->optional()->randomElement(['1/125s', '1/250s', '1/500s', '1/1000s']),
            'f_number' => $this->faker->optional()->randomElement(['f/2.8', 'f/4.0', 'f/5.6', 'f/8.0']),
            'iso' => $this->faker->optional()->randomElement([100, 200, 400, 800, 1600]),
            'focal_length' => $this->faker->optional()->randomElement(['24', '35', '50', '85', '200']),
            
            // GPS (optional)
            'gps_latitude' => $this->faker->optional(0.3)->latitude(),
            'gps_longitude' => $this->faker->optional(0.3)->longitude(),
            'gps_location_name' => $this->faker->optional()->city(),
            
            // Gallery features
            'is_favorite' => $this->faker->boolean(20), // 20% are favorites
            'view_count' => $this->faker->numberBetween(0, 100),
            'last_viewed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'edit_history' => [],
            'album' => $this->faker->optional(0.3)->word(),
        ];
    }

    /**
     * Indicate that the image is a favorite.
     */
    public function favorite(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_favorite' => true,
        ]);
    }

    /**
     * Indicate that the image has faces.
     */
    public function withFaces(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'face_count' => $count,
            'face_encodings' => array_fill(0, $count, array_fill(0, 128, 0.1)),
        ]);
    }

    /**
     * Indicate that the image has full camera metadata.
     */
    public function withCameraMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'camera_make' => 'Canon',
            'camera_model' => 'EOS R5',
            'lens_model' => 'RF 24-70mm F2.8',
            'date_taken' => now()->subDays(rand(1, 365)),
            'exposure_time' => '1/125s',
            'f_number' => 'f/2.8',
            'iso' => 400,
            'focal_length' => '50',
            'exif_data' => [
                'Make' => 'Canon',
                'Model' => 'EOS R5',
                'LensModel' => 'RF 24-70mm F2.8',
            ],
        ]);
    }

    /**
     * Indicate that the image has GPS coordinates.
     */
    public function withGPS(): static
    {
        return $this->state(fn (array $attributes) => [
            'gps_latitude' => $this->faker->latitude(),
            'gps_longitude' => $this->faker->longitude(),
            'gps_location_name' => $this->faker->city(),
        ]);
    }

    /**
     * Indicate that the image is deleted (soft delete).
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}


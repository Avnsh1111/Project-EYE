<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Face clusters - groups of similar faces (one person/pet)
        Schema::create('face_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // User-assigned name
            $table->string('type')->default('person'); // person, pet, unknown
            $table->json('representative_encoding'); // Average face encoding
            $table->string('thumbnail_path')->nullable(); // Best face photo
            $table->integer('photo_count')->default(0); // Number of photos
            $table->timestamps();
            
            $table->index('name');
            $table->index('type');
        });
        
        // Detected faces in images
        Schema::create('detected_faces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_file_id')->constrained()->onDelete('cascade');
            $table->foreignId('face_cluster_id')->nullable()->constrained()->onDelete('set null');
            $table->json('face_encoding'); // 128-d face encoding
            $table->json('face_location')->nullable(); // [top, right, bottom, left]
            $table->float('confidence')->default(1.0);
            $table->timestamps();
            
            $table->index('image_file_id');
            $table->index('face_cluster_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detected_faces');
        Schema::dropIfExists('face_clusters');
    }
};

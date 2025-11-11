<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('image_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Add vector column using raw SQL (pgvector type)
        // CLIP ViT-B/32 produces 512-dimensional embeddings
        DB::statement('ALTER TABLE image_files ADD COLUMN embedding vector(512)');
        
        // Create HNSW index for fast similarity search
        // HNSW works better than IVFFlat for smaller datasets
        DB::statement('CREATE INDEX image_files_embedding_idx ON image_files USING hnsw (embedding vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_files');
    }
};


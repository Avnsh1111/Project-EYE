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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->timestamps();
        });

        // Insert default model settings
        DB::table('settings')->insert([
            [
                'key' => 'captioning_model',
                'value' => 'Salesforce/blip-image-captioning-large',
                'description' => 'Model used for generating image captions',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'embedding_model',
                'value' => 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K',
                'description' => 'Model used for generating image embeddings',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'face_detection_enabled',
                'value' => 'true',
                'description' => 'Enable face detection in images',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ollama_enabled',
                'value' => 'false',
                'description' => 'Enable Ollama for detailed descriptions',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ollama_model',
                'value' => 'llama2',
                'description' => 'Ollama model to use for detailed descriptions',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};


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
        Schema::table('image_files', function (Blueprint $table) {
            $table->text('detailed_description')->nullable()->after('description');
            $table->json('meta_tags')->nullable()->after('detailed_description');
            $table->integer('face_count')->default(0)->after('meta_tags');
            $table->json('face_encodings')->nullable()->after('face_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_files', function (Blueprint $table) {
            $table->dropColumn(['detailed_description', 'meta_tags', 'face_count', 'face_encodings']);
        });
    }
};


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
            // Favorites
            $table->boolean('is_favorite')->default(false)->after('face_encodings');
            
            // Soft deletes (trash functionality)
            $table->softDeletes();
            
            // View count
            $table->integer('view_count')->default(0)->after('is_favorite');
            
            // Last viewed
            $table->timestamp('last_viewed_at')->nullable()->after('view_count');
            
            // Edit history (JSON - stores rotation, filters applied, etc.)
            $table->jsonb('edit_history')->nullable()->after('last_viewed_at');
            
            // Album/collection (for future grouping)
            $table->string('album')->nullable()->after('edit_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_files', function (Blueprint $table) {
            $table->dropColumn([
                'is_favorite',
                'deleted_at',
                'view_count',
                'last_viewed_at',
                'edit_history',
                'album',
            ]);
        });
    }
};


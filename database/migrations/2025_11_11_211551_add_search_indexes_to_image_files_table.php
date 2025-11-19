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
        // Enable pg_trgm extension for trigram similarity (if not already enabled)
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        
        // Add GIN index for full-text search on description (PostgreSQL)
        // This makes ILIKE queries much faster
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS image_files_description_gin_idx ON image_files USING gin (description gin_trgm_ops)');
        } catch (\Exception $e) {
            // Fallback to simple index if extension fails
            DB::statement('CREATE INDEX IF NOT EXISTS image_files_description_idx ON image_files (description) WHERE description IS NOT NULL');
        }
        
        // Add index for prefix searches on original_filename
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS image_files_original_filename_idx ON image_files (original_filename text_pattern_ops)');
        } catch (\Exception $e) {
            // Fallback to simple index
            DB::statement('CREATE INDEX IF NOT EXISTS image_files_original_filename_idx ON image_files (original_filename) WHERE original_filename IS NOT NULL');
        }
        
        // Composite index for common filter combinations
        DB::statement('CREATE INDEX IF NOT EXISTS image_files_status_deleted_idx ON image_files (processing_status, deleted_at) WHERE deleted_at IS NULL');
        
        // Index for completed images only (most common search scenario)
        DB::statement('CREATE INDEX IF NOT EXISTS image_files_completed_idx ON image_files (id) WHERE processing_status = \'completed\' AND deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS image_files_description_gin_idx');
        DB::statement('DROP INDEX IF EXISTS image_files_original_filename_idx');
        DB::statement('DROP INDEX IF EXISTS image_files_status_deleted_idx');
        DB::statement('DROP INDEX IF EXISTS image_files_completed_idx');
    }
};

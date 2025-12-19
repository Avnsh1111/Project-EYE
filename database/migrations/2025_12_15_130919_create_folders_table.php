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
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path')->unique(); // Full path like "2024/December" or "Photos/Vacations"
            $table->foreignId('parent_id')->nullable()->constrained('folders')->onDelete('cascade');
            $table->string('type')->default('custom'); // 'year', 'month', 'media_type', 'event', 'custom'
            $table->string('icon')->nullable(); // Material icon name
            $table->text('description')->nullable();
            $table->integer('file_count')->default(0);
            $table->bigInteger('total_size')->default(0); // in bytes
            $table->json('metadata')->nullable(); // For storing additional data
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Indexes
            $table->index('path');
            $table->index('type');
            $table->index('parent_id');
            $table->index('user_id');
        });

        // Add folder_id to media_files table
        Schema::table('media_files', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('id')->constrained('folders')->onDelete('set null');
            $table->index('folder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropColumn('folder_id');
        });
        
        Schema::dropIfExists('folders');
    }
};

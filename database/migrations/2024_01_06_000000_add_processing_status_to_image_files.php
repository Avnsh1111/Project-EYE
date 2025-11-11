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
            $table->string('processing_status')->default('pending')->after('album'); // pending, processing, completed, failed
            $table->timestamp('processing_started_at')->nullable()->after('processing_status');
            $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at');
            $table->text('processing_error')->nullable()->after('processing_completed_at');
            $table->integer('processing_attempts')->default(0)->after('processing_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_files', function (Blueprint $table) {
            $table->dropColumn([
                'processing_status',
                'processing_started_at',
                'processing_completed_at',
                'processing_error',
                'processing_attempts',
            ]);
        });
    }
};


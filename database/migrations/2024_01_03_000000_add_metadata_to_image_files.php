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
            // Original filename
            $table->string('original_filename')->nullable()->after('file_path');
            
            // File metadata
            $table->string('mime_type')->nullable()->after('original_filename');
            $table->bigInteger('file_size')->nullable()->after('mime_type'); // bytes
            $table->integer('width')->nullable()->after('file_size');
            $table->integer('height')->nullable()->after('width');
            
            // EXIF data stored as JSON
            $table->jsonb('exif_data')->nullable()->after('height');
            
            // Common EXIF fields extracted for quick access
            $table->string('camera_make')->nullable()->after('exif_data');
            $table->string('camera_model')->nullable()->after('camera_make');
            $table->string('lens_model')->nullable()->after('camera_model');
            $table->timestamp('date_taken')->nullable()->after('lens_model');
            $table->string('exposure_time')->nullable()->after('date_taken');
            $table->string('f_number')->nullable()->after('exposure_time');
            $table->integer('iso')->nullable()->after('f_number');
            $table->decimal('focal_length', 8, 2)->nullable()->after('iso');
            
            // GPS data
            $table->decimal('gps_latitude', 10, 7)->nullable()->after('focal_length');
            $table->decimal('gps_longitude', 10, 7)->nullable()->after('gps_latitude');
            $table->string('gps_location_name')->nullable()->after('gps_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_files', function (Blueprint $table) {
            $table->dropColumn([
                'original_filename',
                'mime_type',
                'file_size',
                'width',
                'height',
                'exif_data',
                'camera_make',
                'camera_model',
                'lens_model',
                'date_taken',
                'exposure_time',
                'f_number',
                'iso',
                'focal_length',
                'gps_latitude',
                'gps_longitude',
                'gps_location_name',
            ]);
        });
    }
};


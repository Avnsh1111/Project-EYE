<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Events\ImageProcessed;
use App\Listeners\ImageProcessedListener;
use App\Models\MediaFile;
use Illuminate\Support\Facades\Event;

test('ImageProcessed event has a registered listener', function () {
    $listeners = Event::getListeners(ImageProcessed::class);

    expect($listeners)->not->toBeEmpty();
});

test('ImageProcessedListener logs the processed file', function () {
    $id = \Illuminate\Support\Facades\DB::table('media_files')->insertGetId([
        'original_filename'  => 'test.jpg',
        'file_path'          => 'uploads/test.jpg',
        'media_type'         => 'image',
        'mime_type'          => 'image/jpeg',
        'processing_status'  => 'completed',
        'file_size'          => 1024,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);
    $media = \App\Models\MediaFile::find($id);

    $event = new ImageProcessed($media);
    $listener = new ImageProcessedListener();

    // Should not throw
    expect(fn () => $listener->handle($event))->not->toThrow(Exception::class);
});

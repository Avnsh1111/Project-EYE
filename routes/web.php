<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ImageUploader;
use App\Livewire\InstantImageUploader;
use App\Livewire\ImageSearch;
use App\Livewire\EnhancedImageGallery;
use App\Livewire\Collections;
use App\Livewire\Settings;
use App\Livewire\ProcessingStatus;
use App\Livewire\SystemMonitor;
use App\Livewire\PeopleAndPets;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/upload', ImageUploader::class)->name('upload');
Route::get('/instant-upload', InstantImageUploader::class)->name('instant-upload');
Route::get('/processing-status', ProcessingStatus::class)->name('processing-status');

Route::get('/search', ImageSearch::class)->name('search');

Route::get('/gallery', EnhancedImageGallery::class)->name('gallery');

Route::get('/collections', Collections::class)->name('collections');

Route::get('/settings', Settings::class)->name('settings');

Route::get('/system-monitor', SystemMonitor::class)->name('system-monitor');

Route::get('/people', PeopleAndPets::class)->name('people');

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
use App\Livewire\DocumentManager;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use Illuminate\Support\Facades\Auth;

// Public routes
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('gallery');
    }
    return view('welcome');
})->name('home');

// Authentication routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('forgot-password');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('reset-password');
});

// Logout route
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Protected routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/upload', ImageUploader::class)->name('upload');
    Route::get('/instant-upload', InstantImageUploader::class)->name('instant-upload');
    Route::get('/processing-status', ProcessingStatus::class)->name('processing-status');
    Route::get('/search', ImageSearch::class)->name('search');
    Route::get('/gallery', EnhancedImageGallery::class)->name('gallery');
    Route::get('/collections', Collections::class)->name('collections');
    Route::get('/documents', DocumentManager::class)->name('documents');
    Route::get('/settings', Settings::class)->name('settings');
    Route::get('/system-monitor', SystemMonitor::class)->name('system-monitor');
    Route::get('/people-and-pets', PeopleAndPets::class)->name('people-and-pets');

    // Media streaming routes (video/audio/archive)
    Route::get('/media/{id}/stream', [App\Http\Controllers\MediaController::class, 'stream'])->name('media.stream');
    Route::get('/media/{id}/thumbnail', [App\Http\Controllers\MediaController::class, 'thumbnail'])->name('media.thumbnail');
    Route::get('/media/{id}/download', [App\Http\Controllers\MediaController::class, 'download'])->name('media.download');

    // Document routes
    Route::get('/documents/{id}/preview', [App\Http\Controllers\DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{id}/download', [App\Http\Controllers\DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{id}/thumbnail', [App\Http\Controllers\DocumentController::class, 'thumbnail'])->name('documents.thumbnail');
});

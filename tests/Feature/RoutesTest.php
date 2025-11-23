<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

uses(RefreshDatabase::class);

describe('Application Routes', function () {

    it('home page loads successfully', function () {
        $response = $this->get('/');

        $response->assertStatus(200);
    });

    it('upload page loads successfully', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/upload');

        $response->assertStatus(200);
    });

    it('search page loads successfully', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/search');

        $response->assertStatus(200);
    });

    it('gallery page loads successfully', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/gallery');

        $response->assertStatus(200);
    });

    it('settings page loads successfully', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/settings');

        $response->assertStatus(200);
    });

    it('all routes return HTML content', function () {
        $routes = ['/', '/upload', '/search', '/gallery', '/settings'];

        $routes = ['/', '/upload', '/search', '/gallery', '/settings'];
        $user = User::factory()->create();

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);
            $contentType = $response->headers->get('content-type');
            $this->assertTrue(
                str_contains(strtolower($contentType), 'text/html; charset=utf-8'),
                "Content-Type header mismatch: $contentType"
            );
        }
    });

    it('pages contain necessary meta tags', function () {
        $response = $this->get('/');

        $response->assertSee('charset', false)
            ->assertSee('viewport', false)
            ->assertSee('csrf-token', false);
    });

    it('pages load livewire assets', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/gallery');

        // Livewire directives are compiled, so we check for the compiled output
        $response->assertSee('livewire', false);
    });
});


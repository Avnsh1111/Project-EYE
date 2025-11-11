<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Application Routes', function () {
    
    it('home page loads successfully', function () {
        $response = $this->get('/');

        $response->assertStatus(200);
    });

    it('upload page loads successfully', function () {
        $response = $this->get('/upload');

        $response->assertStatus(200);
    });

    it('search page loads successfully', function () {
        $response = $this->get('/search');

        $response->assertStatus(200);
    });

    it('gallery page loads successfully', function () {
        $response = $this->get('/gallery');

        $response->assertStatus(200);
    });

    it('settings page loads successfully', function () {
        $response = $this->get('/settings');

        $response->assertStatus(200);
    });

    it('all routes return HTML content', function () {
        $routes = ['/', '/upload', '/search', '/gallery', '/settings'];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertHeader('content-type', 'text/html; charset=UTF-8');
        }
    });

    it('pages contain necessary meta tags', function () {
        $response = $this->get('/');

        $response->assertSee('charset', false)
            ->assertSee('viewport', false)
            ->assertSee('csrf-token', false);
    });

    it('pages load livewire assets', function () {
        $response = $this->get('/gallery');

        // Livewire directives are compiled, so we check for the compiled output
        $response->assertSee('livewire', false);
    });
});


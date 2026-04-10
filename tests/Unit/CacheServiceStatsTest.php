<?php

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

test('getStats returns hits and misses counters', function () {
    Cache::flush();
    $service = new CacheService();

    $stats = $service->getStats();

    expect($stats)->toHaveKeys(['prefix', 'default_ttl', 'store', 'hits', 'misses', 'hit_rate']);
});

test('getStats increments hits when cache key exists', function () {
    Cache::flush();
    $service = new CacheService();
    $service->resetStats();

    $service->put('/tmp/test.jpg', ['caption' => 'test']);
    $service->get('/tmp/test.jpg');  // hit

    $stats = $service->getStats();
    expect($stats['hits'])->toBe(1);
});

test('getStats increments misses when cache key missing', function () {
    Cache::flush();
    $service = new CacheService();
    $service->resetStats();

    $service->get('/tmp/nonexistent.jpg');  // miss

    $stats = $service->getStats();
    expect($stats['misses'])->toBe(1);
});

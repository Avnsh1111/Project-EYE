<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Elasticsearch\ClientBuilder as ElasticsearchBuilder;
use App\Services\ElasticsearchEngine;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register MediaFile observer for automatic folder organization
        \App\Models\MediaFile::observe(\App\Observers\MediaFileObserver::class);

        // Register custom Elasticsearch engine for Laravel Scout
        resolve(EngineManager::class)->extend('elasticsearch', function () {
            return new ElasticsearchEngine(
                ElasticsearchBuilder::create()
                    ->setHosts(config('elasticsearch.hosts'))
                    ->build(),
                config('scout.prefix') . 'media_files'
            );
        });
    }
}

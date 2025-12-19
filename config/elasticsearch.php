<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Elasticsearch settings. The default host is
    | set to connect to the Elasticsearch service running via Docker Compose.
    |
    */

    'hosts' => [
        env('ELASTICSEARCH_HOST', 'http://elasticsearch:9200'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Index Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for Elasticsearch indices.
    |
    */

    'index' => [
        'settings' => [
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
            'analysis' => [
                'analyzer' => [
                    'default' => [
                        'type' => 'standard',
                        'stopwords' => '_english_',
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait before timing out a request.
    |
    */

    'timeout' => env('ELASTICSEARCH_TIMEOUT', 30),
];

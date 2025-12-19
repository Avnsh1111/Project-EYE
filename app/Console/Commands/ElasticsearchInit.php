<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchInit extends Command
{
    protected $signature = 'elasticsearch:init';
    protected $description = 'Initialize Elasticsearch index with proper mappings';

    public function handle()
    {
        $this->info('🔧 Initializing Elasticsearch...');

        try {
            $client = ClientBuilder::create()
                ->setHosts(config('elasticsearch.hosts'))
                ->build();

            // Check if Elasticsearch is reachable
            if (!$client->ping()) {
                $this->error('❌ Cannot connect to Elasticsearch');
                return 1;
            }

            $indexName = config('scout.prefix') . 'media_files';

            // Delete index if it exists
            try {
                if ($client->indices()->exists(['index' => $indexName])) {
                    $this->warn('⚠️  Index already exists, deleting...');
                    $client->indices()->delete(['index' => $indexName]);
                }
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }

            // Create index with mappings
            $params = [
                'index' => $indexName,
                'body' => [
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
                    'mappings' => [
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'original_filename' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                            ],
                            'description' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                                'boost' => 3.0,
                            ],
                            'detailed_description' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                                'boost' => 2.0,
                            ],
                            'tags' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                            ],
                            'objects_detected' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                            ],
                            'scene_classification' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                            ],
                            'media_type' => ['type' => 'keyword'],
                            'mime_type' => ['type' => 'keyword'],
                            'date_taken' => ['type' => 'date'],
                            'created_at' => ['type' => 'date'],
                            'is_favorite' => ['type' => 'boolean'],
                        ],
                    ],
                ],
            ];

            $client->indices()->create($params);

            $this->info('✅ Elasticsearch index created successfully!');
            $this->info('📝 Index name: ' . $indexName);
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}

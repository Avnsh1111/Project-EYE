<?php

namespace App\Services;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Support\LazyCollection;

class ElasticsearchEngine extends Engine
{
    protected $elasticsearch;
    protected $index;

    public function __construct(Elasticsearch $elasticsearch, $index)
    {
        $this->elasticsearch = $elasticsearch;
        $this->index = $index;
    }

    /**
     * Update the given model in the index.
     */
    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $params['body'] = [];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'update' => [
                    '_id' => $model->getScoutKey(),
                    '_index' => $this->index,
                ]
            ];
            $params['body'][] = [
                'doc' => $model->toSearchableArray(),
                'doc_as_upsert' => true
            ];
        });

        $this->elasticsearch->bulk($params);
    }

    /**
     * Remove the given model from the index.
     */
    public function delete($models)
    {
        $params['body'] = [];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'delete' => [
                    '_id' => $model->getScoutKey(),
                    '_index' => $this->index,
                ]
            ];
        });

        $this->elasticsearch->bulk($params);
    }

    /**
     * Perform the given search on the engine.
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            'numericFilters' => $this->filters($builder),
            'size' => $builder->limit,
        ]));
    }

    /**
     * Perform the given search on the engine.
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $result = $this->performSearch($builder, [
            'numericFilters' => $this->filters($builder),
            'from' => (($page * $perPage) - $perPage),
            'size' => $perPage,
        ]);

        $result['nbPages'] = $result['hits']['total']['value'] / $perPage;

        return $result;
    }

    /**
     * Perform the given search on the engine.
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'multi_match' => [
                                    'query' => $builder->query,
                                    'fields' => ['description^3', 'detailed_description^2', 'tags', 'original_filename'],
                                    'type' => 'best_fields',
                                    'fuzziness' => 'AUTO',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($sort = $this->sort($builder)) {
            $params['body']['sort'] = $sort;
        }

        if (isset($options['from'])) {
            $params['body']['from'] = $options['from'];
        }

        if (isset($options['size'])) {
            $params['body']['size'] = $options['size'];
        }

        if (isset($options['numericFilters']) && count($options['numericFilters'])) {
            $params['body']['query']['bool']['must'] = array_merge(
                $params['body']['query']['bool']['must'],
                $options['numericFilters']
            );
        }

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->elasticsearch,
                $builder->query,
                $params
            );
        }

        return $this->elasticsearch->search($params);
    }

    /**
     * Get the filter array for the query.
     */
    protected function filters(Builder $builder)
    {
        return collect($builder->wheres)->map(function ($value, $key) {
            return ['match' => [$key => $value]];
        })->values()->all();
    }

    /**
     * Map the given results to instances of the given model.
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($results['hits']['total']['value'] === 0) {
            return $model->newCollection();
        }

        $objectIds = collect($results['hits']['hits'])->pluck('_id')->values()->all();

        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds(
            $builder, $objectIds
        )->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        if ($results['hits']['total']['value'] === 0) {
            return LazyCollection::make($model->newCollection());
        }

        $objectIds = collect($results['hits']['hits'])->pluck('_id')->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->queryScoutModelsByIds(
            $builder, $objectIds
        )->cursor()->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total']['value'];
    }

    /**
     * Flush all of the model's records from the engine.
     */
    public function flush($model)
    {
        $model->newQuery()
            ->orderBy($model->getKeyName())
            ->unsearchable();
    }

    /**
     * Create a search index.
     */
    public function createIndex($name, array $options = [])
    {
        $this->elasticsearch->indices()->create([
            'index' => $name,
            'body' => $options,
        ]);
    }

    /**
     * Delete a search index.
     */
    public function deleteIndex($name)
    {
        $this->elasticsearch->indices()->delete([
            'index' => $name
        ]);
    }

    /**
     * Generates the sort if theres any.
     */
    protected function sort($builder)
    {
        if (count($builder->orders) == 0) {
            return null;
        }

        return collect($builder->orders)->map(function ($order) {
            return [$order['column'] => $order['direction']];
        })->toArray();
    }
}

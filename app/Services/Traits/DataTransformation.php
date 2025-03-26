<?php

namespace App\Services\Traits;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

trait DataTransformation
{
    protected function transformCollection(EloquentCollection $collection, array $meta = []): array
    {
        $resource = new Collection($collection, $this->transformer, $this->resourceKey);
        if (!empty($meta)) {
            $resource->setMeta($meta);
        }
        $data = $this->fractal->createData($resource)->toArray();

        return [
            $this->resourceKey => $data['data']
        ];
    }

    protected function transformPaginated(LengthAwarePaginator $paginator, array $meta = []): array
    {
        $collection = $paginator->getCollection();

        $resource = new Collection($collection, $this->transformer, $this->resourceKey);

        $paginationMeta = [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];

        $resource->setMeta(array_merge($paginationMeta, $meta));
        $data = $this->fractal->createData($resource)->toArray();

        return [
            $this->resourceKey => $data['data'],
            'meta' => $data['meta'],
        ];
    }

    protected function transformItem($item): array
    {
        $resource = new Item($item, $this->transformer, $this->singularResourceKey);
        $data = $this->fractal->createData($resource)->toArray();

        return [
            $this->singularResourceKey => $data['data'],
        ];
    }
}

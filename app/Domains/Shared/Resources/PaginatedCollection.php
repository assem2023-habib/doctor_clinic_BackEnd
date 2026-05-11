<?php

namespace App\Domains\Shared\Resources;

use App\Domains\Shared\Adapters\LaravelPaginationAdapter;
use App\Domains\Shared\Data\PaginatedData;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaginatedCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function paginationInformation($request, array $paginated, array $default): array
    {
        $adapter = new LaravelPaginationAdapter($this->resource);
        $metadata = PaginatedData::fromPaginator($adapter);

        return [
            'meta' => $metadata->toArray(),
        ];
    }
}

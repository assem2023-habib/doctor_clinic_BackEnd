<?php

namespace App\Domains\Shared\Data;

use App\Domains\Shared\Contracts\PaginationInterface;

class PaginatedData
{
    public function __construct(
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $limit,
        public readonly int $total,
        public readonly bool $hasNextPage,
        public readonly bool $hasPreviousPage,
        public readonly ?string $nextPageUrl = null,
        public readonly ?string $previousPageUrl = null,
    ) {}

    public static function fromPaginator(PaginationInterface $paginator): self
    {
        return new self(
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            limit: $paginator->limit(),
            total: $paginator->total(),
            hasNextPage: $paginator->hasNextPage(),
            hasPreviousPage: $paginator->hasPreviousPage(),
            nextPageUrl: $paginator->nextPageUrl(),
            previousPageUrl: $paginator->previousPageUrl(),
        );
    }

    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'limit' => $this->limit,
            'total' => $this->total,
            'has_next_page' => $this->hasNextPage,
            'has_previous_page' => $this->hasPreviousPage,
            'next_page_url' => $this->nextPageUrl,
            'previous_page_url' => $this->previousPageUrl,
        ];
    }
}

<?php

namespace App\Domains\Shared\Adapters;

use App\Domains\Shared\Contracts\PaginationInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LaravelPaginationAdapter implements PaginationInterface
{
    public function __construct(
        private readonly LengthAwarePaginator $paginator,
    ) {}

    public function currentPage(): int
    {
        return $this->paginator->currentPage();
    }

    public function lastPage(): int
    {
        return $this->paginator->lastPage();
    }

    public function limit(): int
    {
        return $this->paginator->perPage();
    }

    public function total(): int
    {
        return $this->paginator->total();
    }

    public function hasNextPage(): bool
    {
        return $this->paginator->hasMorePages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->paginator->currentPage() > 1;
    }

    public function nextPageUrl(): ?string
    {
        return $this->paginator->nextPageUrl();
    }

    public function previousPageUrl(): ?string
    {
        return $this->paginator->previousPageUrl();
    }
}

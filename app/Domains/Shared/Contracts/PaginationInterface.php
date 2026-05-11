<?php

namespace App\Domains\Shared\Contracts;

interface PaginationInterface
{
    public function currentPage(): int;
    public function lastPage(): int;
    public function limit(): int;
    public function total(): int;
    public function hasNextPage(): bool;
    public function hasPreviousPage(): bool;
    public function nextPageUrl(): ?string;
    public function previousPageUrl(): ?string;
}

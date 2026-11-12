<?php

declare(strict_types=1);

namespace App\Core\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function findById(int|string $id): ?Model;

    public function findOrFail(int|string $id): Model;

    /**
     * @param  array<string, mixed>  $where
     */
    public function findFirstWhere(array $where): ?Model;

    /**
     * @param  array<string, mixed>  $where
     */
    public function findWhere(array $where): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateById(int|string $id, array $attributes): Model;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $where
     */
    public function updateWhere(array $attributes, array $where): int;

    public function deleteById(int|string $id): bool;
}

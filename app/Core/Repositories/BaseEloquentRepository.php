<?php

declare(strict_types=1);

namespace App\Core\Repositories;

use App\Core\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseEloquentRepository implements BaseRepositoryInterface
{
    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    protected function newQuery(): Builder
    {
        $class = $this->modelClass();

        return $class::query();
    }

    public function findById(int|string $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->newQuery()->findOrFail($id);
    }

    public function findFirstWhere(array $where): ?Model
    {
        return $this->newQuery()->where($where)->first();
    }

    public function findWhere(array $where): Collection
    {
        return $this->newQuery()->where($where)->get();
    }

    public function create(array $attributes): Model
    {
        return $this->newQuery()->create($attributes);
    }

    public function updateById(int|string $id, array $attributes): Model
    {
        $record = $this->findOrFail($id);
        $record->fill($attributes);
        $record->save();

        return $record->refresh();
    }

    public function updateWhere(array $attributes, array $where): int
    {
        return $this->newQuery()->where($where)->update($attributes);
    }

    public function deleteById(int|string $id): bool
    {
        $record = $this->findById($id);
        if (! $record) {
            return false;
        }

        return (bool) $record->delete();
    }
}

<?php

namespace App\Services\Core;



use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    /**
     * Получить модель для работы
     */
    abstract protected function getModel(): string;

    /**
     * Получить все записи с возможностями:
     * - Пагинация
     * - Подгрузка связей
     * - Фильтрация
     */
    public function getAll(
        array $relations = [],
        bool $paginate = false,
        int $perPage = 15,
        array $filters = []
    ) {
        $query = $this->buildQuery($relations, $filters);

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Построитель запроса с учетом фильтров и связей
     */
    protected function buildQuery(array $relations = [], array $filters = []): Builder
    {
        $query = $this->getModel()::query()->with($relations);

        foreach ($filters as $field => $value) {
            if ($value === null) continue;

            if ($field === 'name') {
                $query->where($field, 'like', '%' . $value . '%');
            } elseif (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * Получить запись по ID с возможностью подгрузки связей
     */
    public function getById(int $id, array $relations = []): Model
    {
        return $this->getModel()::with($relations)->findOrFail($id);
    }

    /**
     * Создать новую запись
     */
    public function create(array $data): Model
    {
        return $this->getModel()::create($data);
    }

    /**
     * Обновить запись
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->getModel()::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    /**
     * Удалить запись
     */
    public function delete(int $id): bool
    {
        $model = $this->getModel()::findOrFail($id);
        return $model->delete();
    }

    /**
     * Найти записи по условиям
     */
    public function findWhere(array $conditions, array $relations = [])
    {
        return $this->getModel()::with($relations)
            ->where($conditions)
            ->get();
    }
}

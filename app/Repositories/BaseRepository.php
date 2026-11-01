<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(protected readonly string $modelClass) {}

    protected function query(): Builder
    {
        return ($this->modelClass)::query();
    }

    public function paginate(): LengthAwarePaginator
    {
        return $this->query()->orderBy('id')->paginate(15)->withQueryString();
    }

    /**
     * @param  array<int, string>  $searchFields
     * @param  array<int, string>  $orderFields
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: Collection}
     */
    protected function serverSide(
        DataTableRequest $request,
        array $searchFields,
        array $orderFields,
        ?Builder $query = null,
    ): array {
        $validated = $request->validated();
        $query ??= $this->query();
        $total = (clone $query)->count();
        $filteredQuery = clone $query;
        $search = trim((string) data_get($validated, 'search.value', ''));

        if ($search !== '') {
            $filteredQuery->where(function (Builder $builder) use ($searchFields, $search): void {
                foreach ($searchFields as $index => $field) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($field, 'like', '%'.$search.'%');
                }
            });
        }

        $filtered = (clone $filteredQuery)->count();
        $orderIndex = (int) data_get($validated, 'order.0.column', -1);
        $orderField = $orderFields[$orderIndex] ?? $query->getModel()->getTable().'.id';
        $direction = data_get($validated, 'order.0.dir') === 'desc' ? 'desc' : 'asc';
        $start = (int) $validated['start'];
        $length = (int) $validated['length'];
        $idField = $query->getModel()->getTable().'.id';

        $records = $filteredQuery
            ->orderBy($orderField, $direction)
            ->when($orderField !== $idField, fn (Builder $builder) => $builder->orderBy($idField))
            ->offset($start)
            ->limit($length)
            ->get();

        return [
            'draw' => (int) $validated['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $records,
        ];
    }

    /**
     * Run a DataTables request against a raw Query Builder query.
     *
     * @param  array<int, string>  $searchFields
     * @param  array<int, string>  $orderFields
     * @param  array{0: string, 1: 'asc'|'desc'}  $defaultOrder
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: SupportCollection}
     */
    protected function serverSideRaw(
        DataTableRequest $request,
        QueryBuilder $query,
        array $searchFields,
        array $orderFields,
        array $defaultOrder = ['id', 'asc'],
    ): array {
        $validated = $request->validated();
        $query = DB::query()->fromSub($query, 'datatable_rows');
        $total = (clone $query)->count();
        $filteredQuery = clone $query;
        $search = trim((string) data_get($validated, 'search.value', ''));

        if ($search !== '') {
            $filteredQuery->where(function (QueryBuilder $builder) use ($searchFields, $search): void {
                foreach ($searchFields as $index => $field) {
                    $column = 'datatable_rows.'.substr($field, strrpos($field, '.') === false ? 0 : strrpos($field, '.') + 1);
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($column, 'like', '%'.$search.'%');
                }
            });
        }

        $orderIndex = (int) data_get($validated, 'order.0.column', -1);
        $orderField = $orderFields[$orderIndex] ?? $defaultOrder[0];
        $orderColumn = 'datatable_rows.'.substr(
            $orderField,
            strrpos($orderField, '.') === false ? 0 : strrpos($orderField, '.') + 1,
        );
        $direction = data_get($validated, 'order.0.dir') === 'desc' ? 'desc' : $defaultOrder[1];
        $filtered = (clone $filteredQuery)->count();
        $records = $filteredQuery
            ->orderBy($orderColumn, $direction)
            ->when($orderColumn !== 'datatable_rows.id', fn (QueryBuilder $builder) => $builder->orderBy('datatable_rows.id'))
            ->offset((int) $validated['start'])
            ->limit((int) $validated['length'])
            ->get();

        return [
            'draw' => (int) $validated['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $records,
        ];
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model $record, array $attributes): Model
    {
        $record->fill($attributes);
        $record->save();

        return $record;
    }

    public function delete(Model $record): void
    {
        $record->delete();
    }
}

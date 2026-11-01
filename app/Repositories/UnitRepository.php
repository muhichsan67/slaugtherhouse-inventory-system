<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;

class UnitRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Unit::class);
    }

    public function options(): Collection
    {
        return Unit::query()->orderBy('name')->get(['id', 'name', 'code']);
    }

    public function dataTable(DataTableRequest $request): array
    {
        return $this->serverSide($request,
            ['units.code', 'units.name'],
            ['units.code', 'units.name'],
        );
    }

    public function isUsed(Unit $unit): bool
    {
        return $unit->items()->exists();
    }
}

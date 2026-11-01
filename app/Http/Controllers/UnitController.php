<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Repositories\UnitRepository;
use App\Services\MasterDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class UnitController extends MasterDataController
{
    private const ROUTE = 'units';

    public function __construct(
        private readonly UnitRepository $units,
        private readonly MasterDataService $masterData,
    ) {}

    public function index(): View
    {
        return $this->indexPage('Units', self::ROUTE, [
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Name'],
        ]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        return $this->dataTableResponse($this->units->dataTable($request), self::ROUTE);
    }

    public function create(): View
    {
        return $this->formPage('Unit', self::ROUTE, [
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true, 'maxlength' => 10],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 50],
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $this->units->create($request->validated());

        return $this->saved(self::ROUTE, 'Unit created successfully.');
    }

    public function edit(Unit $unit): View
    {
        return $this->formPage('Unit', self::ROUTE, [
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true, 'maxlength' => 10],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 50],
        ], $unit);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->units->update($unit, $request->validated());

        return $this->saved(self::ROUTE, 'Unit updated successfully.');
    }

    public function destroy(DeleteMasterDataRequest $request, Unit $unit): RedirectResponse
    {
        $this->masterData->deleteUnit($unit);

        return $this->saved(self::ROUTE, 'Unit deleted successfully.');
    }
}

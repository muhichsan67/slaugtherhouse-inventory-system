<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use App\Repositories\WarehouseRepository;
use App\Services\MasterDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class WarehouseController extends MasterDataController
{
    private const ROUTE = 'warehouses';

    public function __construct(
        private readonly WarehouseRepository $warehouses,
        private readonly MasterDataService $masterData,
    ) {}

    public function index(): View
    {
        return $this->indexPage('Warehouses', self::ROUTE, [
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'address', 'label' => 'Address'],
            ['key' => 'is_active', 'label' => 'Status', 'boolean' => true],
        ]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        return $this->dataTableResponse($this->warehouses->dataTable($request), self::ROUTE);
    }

    public function create(): View
    {
        return $this->formPage('Warehouse', self::ROUTE, $this->fields());
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $this->warehouses->create($request->validated());

        return $this->saved(self::ROUTE, 'Warehouse created successfully.');
    }

    public function edit(Warehouse $warehouse): View
    {
        return $this->formPage('Warehouse', self::ROUTE, $this->fields(), $warehouse);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->warehouses->update($warehouse, $request->validated());

        return $this->saved(self::ROUTE, 'Warehouse updated successfully.');
    }

    public function destroy(DeleteMasterDataRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->masterData->deleteWarehouse($warehouse);

        return $this->saved(self::ROUTE, 'Warehouse deleted successfully.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true, 'maxlength' => 20],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 100],
            ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'maxlength' => 255],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
        ];
    }
}

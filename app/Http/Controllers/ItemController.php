<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Repositories\CategoryRepository;
use App\Repositories\ItemRepository;
use App\Repositories\UnitRepository;
use App\Services\MasterDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ItemController extends MasterDataController
{
    private const ROUTE = 'items';

    public function __construct(
        private readonly ItemRepository $items,
        private readonly CategoryRepository $categories,
        private readonly UnitRepository $units,
        private readonly MasterDataService $masterData,
    ) {}

    public function index(): View
    {
        return $this->indexPage('Items', self::ROUTE, [
            ['key' => 'sku', 'label' => 'SKU'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'category_name', 'label' => 'Category'],
            ['key' => 'unit_code', 'label' => 'Unit'],
            ['key' => 'item_type', 'label' => 'Type'],
            ['key' => 'sale_price', 'label' => 'Sale price', 'format' => 'number', 'decimals' => 2],
            ['key' => 'is_active', 'label' => 'Status', 'boolean' => true],
        ]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        $result = $this->items->dataTable($request);
        $result['data'] = $result['data']->map(fn (object $item): array => [
            ...get_object_vars($item),
            'edit_url' => route(self::ROUTE.'.edit', $item->id),
            'delete_url' => route(self::ROUTE.'.destroy', $item->id),
        ]);

        return response()->json($result);
    }

    public function create(): View
    {
        return $this->formPage('Item', self::ROUTE, $this->fields());
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $this->items->create($request->validated());

        return $this->saved(self::ROUTE, 'Item created successfully.');
    }

    public function edit(Item $item): View
    {
        return $this->formPage('Item', self::ROUTE, $this->fields(), $item);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $this->items->update($item, $request->validated());

        return $this->saved(self::ROUTE, 'Item updated successfully.');
    }

    public function destroy(DeleteMasterDataRequest $request, Item $item): RedirectResponse
    {
        $this->masterData->deleteItem($item);

        return $this->saved(self::ROUTE, 'Item deleted successfully.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'required' => true,
                'options' => $this->categories->options()->mapWithKeys(fn ($category) => [$category->id => $category->name])->all()],
            ['name' => 'unit_id', 'label' => 'Unit', 'type' => 'select', 'required' => true,
                'options' => $this->units->options()->mapWithKeys(fn ($unit) => [$unit->id => $unit->code.' - '.$unit->name])->all()],
            ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'required' => true, 'maxlength' => 30],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 150],
            ['name' => 'item_type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => [
                'live_animal' => 'Live animal',
                'carcass' => 'Carcass',
                'cut' => 'Cut',
                'by_product' => 'By-product',
                'supply' => 'Supply',
            ]],
            ['name' => 'sale_price', 'label' => 'Sale price', 'type' => 'number', 'required' => true, 'min' => '0', 'step' => '0.01'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
        ];
    }
}

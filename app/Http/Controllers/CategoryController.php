<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\MasterDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CategoryController extends MasterDataController
{
    private const ROUTE = 'categories';

    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly MasterDataService $masterData,
    ) {}

    public function index(): View
    {
        return $this->indexPage('Categories', self::ROUTE, [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description'],
        ]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        return $this->dataTableResponse($this->categories->dataTable($request), self::ROUTE);
    }

    public function create(): View
    {
        return $this->formPage('Category', self::ROUTE, [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 100],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'maxlength' => 255],
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return $this->saved(self::ROUTE, 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return $this->formPage('Category', self::ROUTE, [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 100],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'maxlength' => 255],
        ], $category);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return $this->saved(self::ROUTE, 'Category updated successfully.');
    }

    public function destroy(DeleteMasterDataRequest $request, Category $category): RedirectResponse
    {
        $this->masterData->deleteCategory($category);

        return $this->saved(self::ROUTE, 'Category deleted successfully.');
    }
}

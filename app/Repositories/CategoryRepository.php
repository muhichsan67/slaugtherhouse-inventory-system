<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    public function options(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    public function dataTable(DataTableRequest $request): array
    {
        return $this->serverSide($request,
            ['categories.name', 'categories.description'],
            ['categories.name', 'categories.description'],
        );
    }

    public function isUsed(Category $category): bool
    {
        return $category->items()->exists();
    }
}

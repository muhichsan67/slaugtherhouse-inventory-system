<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use App\Models\Item;
use App\Models\Partner;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\CategoryRepository;
use App\Repositories\ItemRepository;
use App\Repositories\PartnerRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Repositories\WarehouseRepository;

final class MasterDataService
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly UnitRepository $units,
        private readonly WarehouseRepository $warehouses,
        private readonly PartnerRepository $partners,
        private readonly ItemRepository $items,
        private readonly UserRepository $users,
    ) {}

    public function deleteCategory(Category $category): void
    {
        $this->deleteIfUnused($this->categories->isUsed($category), 'category');
        $this->categories->delete($category);
    }

    public function deleteUnit(Unit $unit): void
    {
        $this->deleteIfUnused($this->units->isUsed($unit), 'unit');
        $this->units->delete($unit);
    }

    public function deleteWarehouse(Warehouse $warehouse): void
    {
        $this->deleteIfUnused($this->warehouses->isUsed($warehouse), 'warehouse');
        $this->warehouses->delete($warehouse);
    }

    public function deletePartner(Partner $partner): void
    {
        $this->deleteIfUnused($this->partners->isUsed($partner), 'partner');
        $this->partners->delete($partner);
    }

    public function deleteItem(Item $item): void
    {
        $this->deleteIfUnused($this->items->isUsed($item), 'item');
        $this->items->delete($item);
    }

    public function deleteUser(User $user, int $actorId): void
    {
        if ($user->id === $actorId) {
            throw new BusinessRuleException('You cannot delete your own user account.');
        }

        $this->deleteIfUnused($this->users->isUsed($user), 'user');
        $this->users->delete($user);
    }

    private function deleteIfUnused(bool $isUsed, string $recordName): void
    {
        if ($isUsed) {
            throw new BusinessRuleException("This {$recordName} is already used and cannot be deleted. Deactivate it instead.");
        }
    }
}

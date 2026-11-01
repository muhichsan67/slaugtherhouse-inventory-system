<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function dataTable(DataTableRequest $request): array
    {
        $query = User::query()
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'roles.name as role_name',
                'users.is_active',
            ]);

        return $this->serverSide($request,
            ['users.name', 'users.email', 'roles.name'],
            ['users.name', 'users.email', 'roles.name', 'users.is_active'],
            $query,
        );
    }

    public function isUsed(User $user): bool
    {
        return $user->purchaseOrders()->exists()
            || $user->approvedPurchaseOrders()->exists()
            || $user->salesOrders()->exists()
            || $user->receipts()->exists()
            || $user->deliveries()->exists()
            || $user->stockMovements()->exists();
    }
}

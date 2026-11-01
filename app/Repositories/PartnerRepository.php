<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Collection;

class PartnerRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Partner::class);
    }

    public function dataTable(DataTableRequest $request): array
    {
        return $this->serverSide($request,
            ['partners.type', 'partners.name', 'partners.phone', 'partners.address'],
            ['partners.type', 'partners.name', 'partners.phone', 'partners.is_active'],
        );
    }

    public function activeSupplierOptions(): Collection
    {
        return Partner::query()
            ->where('type', 'supplier')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function activeCustomerOptions(): Collection
    {
        return Partner::query()
            ->where('type', 'customer')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function isUsed(Partner $partner): bool
    {
        return $partner->purchaseOrders()->exists() || $partner->salesOrders()->exists();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Models\Partner;
use App\Repositories\PartnerRepository;
use App\Services\MasterDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PartnerController extends MasterDataController
{
    private const ROUTE = 'partners';

    public function __construct(
        private readonly PartnerRepository $partners,
        private readonly MasterDataService $masterData,
    ) {}

    public function index(): View
    {
        return $this->indexPage('Partners', self::ROUTE, [
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'is_active', 'label' => 'Status', 'boolean' => true],
        ]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        return $this->dataTableResponse($this->partners->dataTable($request), self::ROUTE);
    }

    public function create(): View
    {
        return $this->formPage('Partner', self::ROUTE, $this->fields());
    }

    public function store(StorePartnerRequest $request): RedirectResponse
    {
        $this->partners->create($request->validated());

        return $this->saved(self::ROUTE, 'Partner created successfully.');
    }

    public function edit(Partner $partner): View
    {
        return $this->formPage('Partner', self::ROUTE, $this->fields(), $partner);
    }

    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    {
        $this->partners->update($partner, $request->validated());

        return $this->saved(self::ROUTE, 'Partner updated successfully.');
    }

    public function destroy(DeleteMasterDataRequest $request, Partner $partner): RedirectResponse
    {
        $this->masterData->deletePartner($partner);

        return $this->saved(self::ROUTE, 'Partner deleted successfully.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => [
                'supplier' => 'Supplier',
                'customer' => 'Customer',
            ]],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 150],
            ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'maxlength' => 30],
            ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'maxlength' => 255],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
        ];
    }
}

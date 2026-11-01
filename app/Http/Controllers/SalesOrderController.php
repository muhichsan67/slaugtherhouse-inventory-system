<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Requests\SalesOrderActionRequest;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Models\SalesOrder;
use App\Repositories\ItemRepository;
use App\Repositories\PartnerRepository;
use App\Repositories\SalesOrderRepository;
use App\Services\SalesOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderRepository $salesOrders,
        private readonly PartnerRepository $partners,
        private readonly ItemRepository $items,
        private readonly SalesOrderService $service,
    ) {}

    public function index(): View
    {
        return view('sales.sales-orders.index', ['user' => request()->user()]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        $result = $this->salesOrders->dataTable($request);
        $result['data'] = collect($result['data'])->map(fn ($salesOrder): array => [
            'number' => $salesOrder->number,
            'customer' => $salesOrder->customer,
            'order_date' => $salesOrder->order_date,
            'total' => $salesOrder->total,
            'status' => $salesOrder->status,
            'view_url' => route('sales-orders.show', $salesOrder->id),
        ]);

        return response()->json($result);
    }

    public function create(): View
    {
        return $this->formPage();
    }

    public function store(StoreSalesOrderRequest $request): RedirectResponse
    {
        $salesOrder = $this->service->create(
            $request->validated(),
            (int) $request->user()->getAuthIdentifier(),
        );

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Sales order created as a draft.');
    }

    public function show(SalesOrder $salesOrder): View
    {
        return view('sales.sales-orders.show', [
            'salesOrder' => $this->salesOrders->find($salesOrder->id),
            'user' => request()->user(),
        ]);
    }

    public function edit(SalesOrder $salesOrder): View
    {
        return $this->formPage($this->service->draft($salesOrder->id));
    }

    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->service->updateDraft($salesOrder->id, $request->validated());

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Sales order updated.');
    }

    public function confirm(SalesOrderActionRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->service->confirm($salesOrder->id);

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Sales order confirmed.');
    }

    public function cancel(SalesOrderActionRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->service->cancel($salesOrder->id);

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Sales order cancelled.');
    }

    private function formPage(?SalesOrder $salesOrder = null): View
    {
        return view('sales.sales-orders.form', [
            'salesOrder' => $salesOrder,
            'customers' => $this->partners->activeCustomerOptions(),
            'items' => $this->items->activeSalesOptions(),
            'user' => request()->user(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Requests\StoreDeliveryRequest;
use App\Models\Delivery;
use App\Repositories\DeliveryRepository;
use App\Repositories\WarehouseRepository;
use App\Services\DeliveryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DeliveryController extends Controller
{
    public function __construct(
        private readonly DeliveryRepository $deliveries,
        private readonly WarehouseRepository $warehouses,
        private readonly DeliveryService $service,
    ) {}

    public function index(): View
    {
        return view('inventory.deliveries.index', ['user' => request()->user()]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        $result = $this->deliveries->dataTable($request);
        $result['data'] = collect($result['data'])->map(fn ($delivery): array => [
            'number' => $delivery->number,
            'sales_order' => $delivery->sales_order,
            'customer' => $delivery->customer,
            'warehouse' => $delivery->warehouse_code.' - '.$delivery->warehouse,
            'delivered_at' => $delivery->delivered_at,
            'delivered_by' => $delivery->delivered_by,
            'status' => $delivery->status,
            'view_url' => route('deliveries.show', $delivery->id),
        ]);

        return response()->json($result);
    }

    public function confirmedOrders(DataTableRequest $request): JsonResponse
    {
        $result = $this->deliveries->confirmedOrders($request);
        $result['data'] = collect($result['data'])->map(fn ($salesOrder): array => [
            'number' => $salesOrder->number,
            'customer' => $salesOrder->customer,
            'order_date' => $salesOrder->order_date,
            'total' => $salesOrder->total,
            'deliver_url' => route('deliveries.create', ['salesOrder' => $salesOrder->id]),
        ]);

        return response()->json($result);
    }

    public function create(int $salesOrder): View
    {
        return view('inventory.deliveries.create', [
            'salesOrder' => $this->service->salesOrderForDelivery($salesOrder),
            'warehouses' => $this->warehouses->activeOptions(),
            'user' => request()->user(),
        ]);
    }

    public function store(StoreDeliveryRequest $request): RedirectResponse
    {
        $delivery = $this->service->post(
            (int) $request->validated('sales_order_id'),
            (int) $request->validated('warehouse_id'),
            (int) $request->user()->getAuthIdentifier(),
        );

        return redirect()->route('deliveries.show', $delivery)
            ->with('success', 'Delivery posted and stock updated.');
    }

    public function show(Delivery $delivery): View
    {
        return view('inventory.deliveries.show', [
            'delivery' => $this->deliveries->find($delivery->id),
            'user' => request()->user(),
        ]);
    }
}

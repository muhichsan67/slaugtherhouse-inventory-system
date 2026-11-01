<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Requests\StoreReceiptRequest;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\ReceiptRepository;
use App\Repositories\WarehouseRepository;
use App\Services\ReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ReceiptController extends Controller
{
    public function __construct(
        private readonly ReceiptRepository $receipts,
        private readonly PurchaseOrderRepository $purchaseOrders,
        private readonly WarehouseRepository $warehouses,
        private readonly ReceiptService $service,
    ) {}

    public function index(): View
    {
        return view('inventory.receipts.index', [
            'user' => request()->user(),
        ]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        $result = $this->receipts->dataTable($request);
        $result['data'] = collect($result['data'])->map(fn ($receipt): array => [
            'number' => $receipt->number,
            'purchase_order' => $receipt->purchase_order,
            'warehouse' => $receipt->warehouse_code.' - '.$receipt->warehouse,
            'received_at' => $receipt->received_at,
            'received_by' => $receipt->received_by,
            'view_url' => route('receipts.show', $receipt->id),
        ]);

        return response()->json($result);
    }

    public function receivablePurchaseOrders(DataTableRequest $request): JsonResponse
    {
        $result = $this->purchaseOrders->receivableDataTable($request);
        $result['data'] = collect($result['data'])->map(fn ($purchaseOrder): array => [
            'number' => $purchaseOrder->number,
            'supplier' => $purchaseOrder->supplier,
            'order_date' => $purchaseOrder->order_date,
            'receive_url' => route('receipts.create', $purchaseOrder->id),
        ]);

        return response()->json($result);
    }

    public function create(PurchaseOrder $purchaseOrder): View
    {
        return view('inventory.receipts.create', [
            'purchaseOrder' => $this->service->receivableOrder($purchaseOrder->id),
            'warehouses' => $this->warehouses->activeOptions(),
            'user' => request()->user(),
        ]);
    }

    public function store(StoreReceiptRequest $request): RedirectResponse
    {
        $receipt = $this->service->post(
            (int) $request->validated('purchase_order_id'),
            (int) $request->validated('warehouse_id'),
            (int) $request->user()->getAuthIdentifier(),
        );

        return redirect()->route('receipts.show', $receipt)
            ->with('success', 'Receipt posted and stock updated.');
    }

    public function show(Receipt $receipt): View
    {
        return view('inventory.receipts.show', [
            'receipt' => $this->receipts->find($receipt->id),
            'user' => request()->user(),
        ]);
    }
}

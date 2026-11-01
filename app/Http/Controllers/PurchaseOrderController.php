<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ApprovePurchaseOrderRequest;
use App\Http\Requests\CancelPurchaseOrderRequest;
use App\Http\Requests\DataTableRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Repositories\ItemRepository;
use App\Repositories\PartnerRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Services\PurchaseOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderRepository $purchaseOrders,
        private readonly PartnerRepository $partners,
        private readonly ItemRepository $items,
        private readonly PurchaseOrderService $service,
    ) {}

    public function index(): View
    {
        return view('purchasing.purchase-orders.index', [
            'user' => request()->user(),
        ]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        $result = $this->purchaseOrders->dataTable($request);
        $result['data'] = collect($result['data'])
            ->map(fn ($purchaseOrder): array => [
                'number' => $purchaseOrder->number,
                'supplier' => $purchaseOrder->supplier,
                'order_date' => $purchaseOrder->order_date,
                'total' => $purchaseOrder->total,
                'status' => $purchaseOrder->status,
                'view_url' => route('purchase-orders.show', $purchaseOrder->id),
            ])
            ->values()
            ->all();

        return response()->json($result);
    }

    public function create(): View
    {
        return $this->formPage();
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $purchaseOrder = $this->service->create(
            $request->validated(),
            (int) $request->user()->getAuthIdentifier(),
        );

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order created as a draft.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder = $this->purchaseOrders->find($purchaseOrder->id);

        return view('purchasing.purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder,
            'hasReceipt' => $this->purchaseOrders->hasReceipt($purchaseOrder->id),
            'user' => request()->user(),
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        return $this->formPage($this->service->draft($purchaseOrder->id));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->updateDraft($purchaseOrder->id, $request->validated());

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated.');
    }

    public function approve(ApprovePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->approve($purchaseOrder->id, (int) $request->user()->getAuthIdentifier());

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order approved.');
    }

    public function cancel(CancelPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->cancel($purchaseOrder->id);

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order cancelled.');
    }

    private function formPage(?PurchaseOrder $purchaseOrder = null): View
    {
        return view('purchasing.purchase-orders.form', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => $this->partners->activeSupplierOptions(),
            'items' => $this->items->activeOptions(),
            'user' => request()->user(),
        ]);
    }
}

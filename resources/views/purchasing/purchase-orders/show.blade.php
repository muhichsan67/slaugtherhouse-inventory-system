@extends('layouts.app')

@section('title', $purchaseOrder->number)

@section('content')
    <div class="mb-4 page-head d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="h3 mb-1">{{ $purchaseOrder->number }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.index') }}">Purchase Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $purchaseOrder->number }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($purchaseOrder->status === 'draft')
                <a class="btn btn-outline-primary" href="{{ route('purchase-orders.edit', $purchaseOrder) }}">Edit draft</a>
                <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder) }}"
                    data-confirm="Approve this purchase order?">
                    @csrf
                    <button class="btn btn-primary" type="submit">Approve</button>
                </form>
            @endif
            @if (in_array($purchaseOrder->status, ['draft', 'approved'], true))
                <form method="POST" action="{{ route('purchase-orders.cancel', $purchaseOrder) }}"
                    data-confirm="Cancel this purchase order?">
                    @csrf
                    <button class="btn btn-outline-danger" type="submit">Cancel</button>
                </form>
            @endif
            <a class="btn btn-outline-secondary" href="{{ route('purchase-orders.index') }}">Back</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Supplier</div><div class="fw-semibold">{{ $purchaseOrder->partner->name }}</div></div>
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Order date</div><div>{{ $purchaseOrder->order_date->format('Y-m-d') }}</div></div>
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Status</div><div class="text-capitalize">{{ $purchaseOrder->status }}</div></div>
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Created by</div><div>{{ $purchaseOrder->user->name }}</div></div>
                @if ($purchaseOrder->approver)
                    <div class="col-sm-6"><div class="small text-secondary">Approved by</div><div>{{ $purchaseOrder->approver->name }} on @displayDateTime($purchaseOrder->approved_at)</div></div>
                @endif
                @if ($purchaseOrder->notes)
                    <div class="col-12"><div class="small text-secondary">Notes</div><div>{{ $purchaseOrder->notes }}</div></div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Order lines</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>SKU</th><th>Item</th><th>Quantity</th><th>Unit price</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                        @foreach ($purchaseOrder->lines as $line)
                            <tr>
                                <td>{{ $line->item->sku }}</td>
                                <td>{{ $line->item->name }}</td>
                                <td>@displayNumber($line->qty, 3)</td>
                                <td>@displayNumber($line->unit_price, 2)</td>
                                <td class="text-end">@displayNumber($line->subtotal, 2)</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end">@displayNumber($purchaseOrder->total, 2)</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

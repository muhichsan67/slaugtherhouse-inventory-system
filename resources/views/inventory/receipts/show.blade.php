@extends('layouts.app')

@section('title', $receipt->number)

@section('content')
    <div class="mb-4 page-head d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="h3 mb-1">{{ $receipt->number }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('receipts.index') }}">Receipts</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $receipt->number }}</li>
                </ol>
            </nav>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('receipts.index') }}">Back to receipts</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Purchase order</div><div>{{ $receipt->purchaseOrder->number }}</div></div>
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Supplier</div><div>{{ $receipt->purchaseOrder->partner->name }}</div></div>
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Warehouse</div><div>{{ $receipt->warehouse->code }} - {{ $receipt->warehouse->name }}</div></div>
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Received at</div><div>@displayDateTime($receipt->received_at)</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Received items</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>SKU</th><th>Item</th><th>Quantity</th><th>Unit cost</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                        @foreach ($receipt->lines as $line)
                            <tr>
                                <td>{{ $line->item->sku }}</td>
                                <td>{{ $line->item->name }}</td>
                                <td>@displayNumber($line->qty_received, 3)</td>
                                <td>@displayNumber($line->unit_cost, 2)</td>
                                <td class="text-end">@displayNumber($line->subtotal, 2)</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total</th>
                            <th class="text-end">@displayNumber($receipt->purchaseOrder->total, 2)</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

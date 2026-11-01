@extends('layouts.app')

@section('title', 'Receive '.$purchaseOrder->number)

@section('content')
    <div class="mb-4 page-head">
        <h1 class="h3 mb-1">Receive {{ $purchaseOrder->number }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('receipts.index') }}">Receipts</a></li>
                <li class="breadcrumb-item active" aria-current="page">Post receipt</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="small text-secondary">Supplier</div><div class="fw-semibold">{{ $purchaseOrder->partner->name }}</div></div>
                <div class="col-md-4"><div class="small text-secondary">Order date</div><div>{{ $purchaseOrder->order_date->format('Y-m-d') }}</div></div>
                <div class="col-md-4"><div class="small text-secondary">Purchase order total</div><div>@displayNumber($purchaseOrder->total, 2)</div></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Items to receive in full</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>SKU</th><th>Item</th><th>Quantity</th><th>Unit cost</th><th class="text-end">Subtotal</th></tr></thead>
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
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('receipts.store') }}">
                @csrf
                <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="warehouse_id">Destination warehouse</label>
                        <select class="form-select @error('warehouse_id') is-invalid @enderror" name="warehouse_id" id="warehouse_id" required>
                            <option value="">Choose warehouse...</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id)>
                                    {{ $warehouse->code }} - {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Post full receipt
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route('receipts.index') }}">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

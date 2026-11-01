@extends('layouts.app')

@section('title', 'Deliver '.$salesOrder->number)

@section('content')
    <div class="mb-4 page-head">
        <h1 class="h3 mb-1">Deliver {{ $salesOrder->number }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('deliveries.index') }}">Deliveries</a></li>
                <li class="breadcrumb-item active" aria-current="page">Post delivery</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="small text-secondary">Customer</div><div class="fw-semibold">{{ $salesOrder->partner->name }}</div></div>
                <div class="col-md-4"><div class="small text-secondary">Order date</div><div>{{ $salesOrder->order_date->format('Y-m-d') }}</div></div>
                <div class="col-md-4"><div class="small text-secondary">Sales order total</div><div>@displayNumber($salesOrder->total, 2)</div></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Items to deliver in full</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>SKU</th><th>Item</th><th>Quantity</th><th>Unit price</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                        @foreach ($salesOrder->lines as $line)
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
            <form method="POST" action="{{ route('deliveries.store') }}">
                @csrf
                <input type="hidden" name="sales_order_id" value="{{ $salesOrder->id }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="warehouse_id">Source warehouse</label>
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
                            <i class="bi bi-box-arrow-up-right me-1"></i>Post full delivery
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route('deliveries.index') }}">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

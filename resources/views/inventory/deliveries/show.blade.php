@extends('layouts.app')

@section('title', $delivery->number)

@section('content')
    <div class="mb-4 page-head d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="h3 mb-1">{{ $delivery->number }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('deliveries.index') }}">Deliveries</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $delivery->number }}</li>
                </ol>
            </nav>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('deliveries.index') }}">Back</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Sales order</div><div>{{ $delivery->salesOrder->number }}</div></div>
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Customer</div><div>{{ $delivery->salesOrder->partner->name }}</div></div>
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Warehouse</div><div>{{ $delivery->warehouse->code }} - {{ $delivery->warehouse->name }}</div></div>
                <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Delivered at</div><div>@displayDateTime($delivery->delivered_at)</div></div>
                <div class="col-sm-6"><div class="small text-secondary">Delivered by</div><div>{{ $delivery->user->name }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Delivered lines and inventory cost</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>SKU</th><th>Item</th><th>Quantity</th><th>Average unit cost</th><th class="text-end">Cost of goods sold</th></tr></thead>
                    <tbody>
                        @foreach ($delivery->lines as $line)
                            <tr>
                                <td>{{ $line->item->sku }}</td>
                                <td>{{ $line->item->name }}</td>
                                <td>@displayNumber($line->qty, 3)</td>
                                <td>@displayNumber($line->unit_cost, 2)</td>
                                <td class="text-end">@displayNumber($line->subtotal, 2)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

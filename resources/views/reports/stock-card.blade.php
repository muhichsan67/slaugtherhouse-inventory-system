@extends('layouts.app')

@section('title', 'Stock Card')

@php
    $columns = [
        ['key' => 'occurred_at', 'label' => 'Occurred at', 'format' => 'datetime'],
        ['key' => 'type', 'label' => 'Movement'],
        ['key' => 'qty', 'label' => 'Quantity change', 'format' => 'number', 'decimals' => 3],
        ['key' => 'unit_cost', 'label' => 'Unit cost', 'format' => 'number', 'decimals' => 2],
        ['key' => 'ref_type', 'label' => 'Reference type'],
        ['key' => 'ref_id', 'label' => 'Reference'],
        ['key' => 'running_qty', 'label' => 'Running balance', 'format' => 'number', 'decimals' => 3],
        ['key' => 'notes', 'label' => 'Notes'],
    ];
@endphp

@once
    @push('styles')
        <link href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
        <link href="{{ asset('vendor/datatables/css/responsive.bootstrap5.min.css') }}" rel="stylesheet">
    @endpush
@endonce

@section('content')
    <div class="mb-4 page-head">
        <h1 class="h3 mb-1">Stock Card</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Stock Card</li>
            </ol>
        </nav>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label" for="stockCardItem">Item</label>
                    <select class="form-select" id="stockCardItem" data-table-filter="item_id">
                        <option value="">All</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->sku }} - {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label" for="stockCardWarehouse">Warehouse</label>
                    <select class="form-select" id="stockCardWarehouse" data-table-filter="warehouse_id">
                        <option value="">All</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->code }} - {{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="stockCardTable" class="table table-hover align-middle w-100" data-source="{{ route('reports.stock-card.data') }}">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th scope="col">{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/responsive.bootstrap5.min.js') }}"></script>
    <script>
        $(function () {
            const table = document.getElementById('stockCardTable');
            const filters = document.querySelectorAll('[data-table-filter]');
            const showTableError = function (xhr) {
                const cardBody = table.closest('.card-body');
                const alert = cardBody.querySelector('[data-table-error]') ?? document.createElement('div');
                alert.className = 'alert alert-danger mt-3';
                alert.setAttribute('role', 'alert');
                alert.dataset.tableError = '';
                alert.textContent = xhr.responseJSON?.message ??
                    'Unable to load table data. Refresh the page or contact an administrator.';
                if (!alert.isConnected) {
                    cardBody.prepend(alert);
                }
            };
            const dataTable = $('#stockCardTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                deferRender: true,
                ajax: {
                    url: table.dataset.source,
                    type: 'GET',
                    data: function (data) {
                        filters.forEach(function (filter) {
                            data[filter.dataset.tableFilter] = filter.value;
                        });
                    },
                    error: function (xhr) {
                        showTableError(xhr);
                    }
                },
                columns: @json($columns).map(function (column) {
                    return {
                        data: column.key,
                        orderable: false,
                        render: column.format === 'datetime'
                            ? function (value, type) {
                                return type === 'display' ? window.appFormatDateTime(value) : value;
                            }
                            : column.format === 'number'
                                ? function (value, type) {
                                    return type === 'display'
                                        ? window.appFormatNumber(value, column.decimals)
                                        : value;
                                }
                                : $.fn.dataTable.render.text()
                    };
                }),
                order: [],
                language: {
                    emptyTable: 'Choose an item and warehouse to view stock movements.',
                    zeroRecords: 'No stock movements match your search.',
                    infoEmpty: 'No stock movements to display.',
                    processing: 'Loading records...'
                }
            });

            filters.forEach(function (filter) {
                filter.addEventListener('change', function () {
                    dataTable.ajax.reload();
                });
            });
        });
    </script>
@endpush

@extends('layouts.app')

@section('title', 'Stock Balances')

@php
    $columns = [
        ['key' => 'sku', 'label' => 'SKU'],
        ['key' => 'item', 'label' => 'Item'],
        ['key' => 'unit', 'label' => 'Unit'],
        ['key' => 'warehouse', 'label' => 'Warehouse'],
        ['key' => 'qty', 'label' => 'Quantity', 'format' => 'number', 'decimals' => 3],
        ['key' => 'avg_cost', 'label' => 'Average cost', 'format' => 'number', 'decimals' => 2],
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
        <h1 class="h3 mb-1">Stock Balances</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Stock Balances</li>
            </ol>
        </nav>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="stockBalancesTable" class="table table-hover align-middle w-100" data-source="{{ route('stocks.data') }}">
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
            const columns = @json($columns);
            const showTableError = function (table, xhr) {
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
            const table = document.getElementById('stockBalancesTable');

            $('#stockBalancesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                deferRender: true,
                ajax: {
                    url: table.dataset.source,
                    type: 'GET',
                    error: function (xhr) {
                        showTableError(table, xhr);
                    }
                },
                columns: columns.map(function (column) {
                    return {
                        data: column.key,
                        render: column.format === 'number'
                            ? function (value, type) {
                                return type === 'display'
                                    ? window.appFormatNumber(value, column.decimals)
                                    : value;
                            }
                            : $.fn.dataTable.render.text()
                    };
                }),
                order: [[0, 'asc']],
                language: {
                    emptyTable: 'No stock balance data is available yet.',
                    zeroRecords: 'No stock balances match your search.',
                    infoEmpty: 'No stock balances to display.',
                    infoFiltered: '(filtered from _MAX_ total stock balances)',
                    processing: 'Loading records...'
                }
            });
        });
    </script>
@endpush

@extends('layouts.app')

@section('title', 'Deliveries')

@php
    $readyColumns = [
        ['key' => 'number', 'label' => 'Sales order'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'order_date', 'label' => 'Order date', 'format' => 'date'],
        ['key' => 'total', 'label' => 'Total', 'format' => 'number', 'decimals' => 2],
    ];
    $deliveryColumns = [
        ['key' => 'number', 'label' => 'Delivery'],
        ['key' => 'sales_order', 'label' => 'Sales order'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'warehouse', 'label' => 'Warehouse'],
        ['key' => 'delivered_at', 'label' => 'Delivered at', 'format' => 'datetime'],
        ['key' => 'delivered_by', 'label' => 'Delivered by'],
        ['key' => 'status', 'label' => 'Status'],
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
        <h1 class="h3 mb-1">Deliveries</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Deliveries</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h6 mb-0">Confirmed sales orders ready for delivery</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="confirmedSalesOrdersTable" class="table table-hover align-middle w-100" data-source="{{ route('deliveries.sales-orders.data') }}">
                    <thead>
                        <tr>
                            @foreach ($readyColumns as $column)
                                <th scope="col">{{ $column['label'] }}</th>
                            @endforeach
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="h6 mb-0">Posted deliveries</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="deliveriesTable" class="table table-hover align-middle w-100" data-source="{{ route('deliveries.data') }}">
                    <thead>
                        <tr>
                            @foreach ($deliveryColumns as $column)
                                <th scope="col">{{ $column['label'] }}</th>
                            @endforeach
                            <th scope="col" class="text-end">Actions</th>
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
            const escapeHtml = function (value) {
                return String(value ?? '').replace(/[&<>"']/g, function (character) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
                });
            };
            const readyColumns = @json($readyColumns).map(function (column) {
                return {
                    data: column.key,
                    render: column.format === 'number'
                        ? function (value, type) {
                            return type === 'display'
                                ? window.appFormatNumber(value, column.decimals)
                                : value;
                        }
                        : column.format === 'date'
                            ? function (value, type) {
                                return type === 'display' ? window.appFormatDate(value) : value;
                            }
                            : $.fn.dataTable.render.text()
                };
            });
            const readyTable = document.getElementById('confirmedSalesOrdersTable');
            readyColumns.push({
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-end text-nowrap',
                render: function (value, type, record) {
                    if (type !== 'display' || !record.deliver_url) {
                        return '';
                    }

                    return '<a class="btn btn-sm btn-primary" href="' + escapeHtml(record.deliver_url) + '">Deliver</a>';
                }
            });

            $('#confirmedSalesOrdersTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                deferRender: true,
                ajax: {
                    url: readyTable.dataset.source,
                    type: 'GET',
                    error: function (xhr) {
                        showTableError(readyTable, xhr);
                    }
                },
                columns: readyColumns,
                order: [[0, 'asc']],
                language: {
                    emptyTable: 'No confirmed sales orders are ready for delivery.',
                    zeroRecords: 'No sales orders match your search.',
                    infoEmpty: 'No sales orders to display.',
                    processing: 'Loading records...'
                }
            });

            const statusColors = { posted: 'success', void: 'dark' };
            const deliveryColumns = @json($deliveryColumns).map(function (column) {
                return {
                    data: column.key,
                    render: column.key === 'status'
                        ? function (value, type) {
                            if (type !== 'display') {
                                return value ?? '';
                            }

                            const label = String(value ?? '');
                            const color = statusColors[label.toLowerCase()] ?? 'secondary';
                            return '<span class="badge text-bg-' + color + '">' + escapeHtml(label) + '</span>';
                        }
                        : column.format === 'datetime'
                            ? function (value, type) {
                                return type === 'display' ? window.appFormatDateTime(value) : value;
                            }
                        : $.fn.dataTable.render.text()
                };
            });
            const deliveryTable = document.getElementById('deliveriesTable');
            deliveryColumns.push({
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-end text-nowrap',
                render: function (value, type, record) {
                    if (type !== 'display' || !record.view_url) {
                        return '';
                    }

                    return '<a class="btn btn-sm btn-outline-primary" href="' + escapeHtml(record.view_url) + '">View</a>';
                }
            });

            $('#deliveriesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                deferRender: true,
                ajax: {
                    url: deliveryTable.dataset.source,
                    type: 'GET',
                    error: function (xhr) {
                        showTableError(deliveryTable, xhr);
                    }
                },
                columns: deliveryColumns,
                order: [[0, 'asc']],
                language: {
                    emptyTable: 'No delivery data is available yet.',
                    zeroRecords: 'No deliveries match your search.',
                    infoEmpty: 'No deliveries to display.',
                    processing: 'Loading records...'
                }
            });
        });
    </script>
@endpush

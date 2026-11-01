@extends('layouts.app')

@section('title', 'Receipts')

@php
    $receivableColumns = [
        ['key' => 'number', 'label' => 'Purchase order'],
        ['key' => 'supplier', 'label' => 'Supplier'],
        ['key' => 'order_date', 'label' => 'Order date', 'format' => 'date'],
    ];
    $receiptColumns = [
        ['key' => 'number', 'label' => 'Receipt'],
        ['key' => 'purchase_order', 'label' => 'Purchase order'],
        ['key' => 'warehouse', 'label' => 'Warehouse'],
        ['key' => 'received_at', 'label' => 'Received at', 'format' => 'datetime'],
        ['key' => 'received_by', 'label' => 'Received by'],
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
        <h1 class="h3 mb-1">Receipts</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Receipts</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h6 mb-0">Approved purchase orders ready to receive</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="receivablePurchaseOrdersTable" class="table table-hover align-middle w-100" data-source="{{ route('receipts.purchase-orders.data') }}">
                    <thead>
                        <tr>
                            @foreach ($receivableColumns as $column)
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
            <h2 class="h6 mb-0">Posted receipts</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="receiptsTable" class="table table-hover align-middle w-100" data-source="{{ route('receipts.data') }}">
                    <thead>
                        <tr>
                            @foreach ($receiptColumns as $column)
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
            const receivableColumns = @json($receivableColumns).map(function (column) {
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
            const receivableTable = document.getElementById('receivablePurchaseOrdersTable');
            receivableColumns.push({
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-end text-nowrap',
                render: function (value, type, record) {
                    if (type !== 'display' || !record.receive_url) {
                        return '';
                    }

                    return '<a class="btn btn-sm btn-primary" href="' + escapeHtml(record.receive_url) + '">Receive</a>';
                }
            });

            $('#receivablePurchaseOrdersTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                deferRender: true,
                ajax: {
                    url: receivableTable.dataset.source,
                    type: 'GET',
                    error: function (xhr) {
                        showTableError(receivableTable, xhr);
                    }
                },
                columns: receivableColumns,
                order: [[0, 'asc']],
                language: {
                    emptyTable: 'No approved purchase orders are ready to receive.',
                    zeroRecords: 'No purchase orders match your search.',
                    infoEmpty: 'No purchase orders to display.',
                    processing: 'Loading records...'
                }
            });

            const receiptColumns = @json($receiptColumns).map(function (column) {
                return {
                    data: column.key,
                    render: column.format === 'datetime'
                        ? function (value, type) {
                            return type === 'display' ? window.appFormatDateTime(value) : value;
                        }
                        : $.fn.dataTable.render.text()
                };
            });
            const receiptTable = document.getElementById('receiptsTable');
            receiptColumns.push({
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

            $('#receiptsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                deferRender: true,
                ajax: {
                    url: receiptTable.dataset.source,
                    type: 'GET',
                    error: function (xhr) {
                        showTableError(receiptTable, xhr);
                    }
                },
                columns: receiptColumns,
                order: [[0, 'asc']],
                language: {
                    emptyTable: 'No receipt data is available yet.',
                    zeroRecords: 'No receipts match your search.',
                    infoEmpty: 'No receipts to display.',
                    processing: 'Loading records...'
                }
            });
        });
    </script>
@endpush

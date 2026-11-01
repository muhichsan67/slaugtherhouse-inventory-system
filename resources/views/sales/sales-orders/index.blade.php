@extends('layouts.app')

@section('title', 'Sales Orders')

@php
    $columns = [
        ['key' => 'number', 'label' => 'Number'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'order_date', 'label' => 'Order date', 'format' => 'date'],
        ['key' => 'total', 'label' => 'Total', 'format' => 'number', 'decimals' => 2],
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
    <div class="mb-4 page-head d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="h3 mb-1">Sales Orders</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sales Orders</li>
                </ol>
            </nav>
        </div>
        <a class="btn btn-primary" href="{{ route('sales-orders.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Create sales order
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="salesOrdersTable" class="table table-hover align-middle w-100" data-source="{{ route('sales-orders.data') }}">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
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
            const escapeHtml = function (value) {
                return String(value ?? '').replace(/[&<>"']/g, function (character) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
                });
            };
            const statusColors = {
                draft: 'secondary',
                confirmed: 'primary',
                delivered: 'success',
                cancelled: 'danger'
            };
            const dataColumns = columns.map(function (column) {
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
                        : column.format === 'number'
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

            dataColumns.push({
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

            const table = document.getElementById('salesOrdersTable');
            $('#salesOrdersTable').DataTable({
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
                columns: dataColumns,
                order: [[0, 'asc']],
                language: {
                    emptyTable: 'No sales order data is available yet.',
                    zeroRecords: 'No sales orders match your search.',
                    infoEmpty: 'No sales orders to display.',
                    infoFiltered: '(filtered from _MAX_ total sales orders)',
                    processing: 'Loading records...'
                }
            });
        });
    </script>
@endpush

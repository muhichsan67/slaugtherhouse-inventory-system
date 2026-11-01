@extends('layouts.app')

@section('title', 'Profit & Loss')

@php
    $columns = [
        ['key' => 'name', 'label' => 'Source'],
        ['key' => 'type', 'label' => 'Type'],
        ['key' => 'amount', 'label' => 'Amount', 'format' => 'number', 'decimals' => 2],
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
        <h1 class="h3 mb-1">Profit &amp; Loss</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profit &amp; Loss</li>
            </ol>
        </nav>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <p class="text-secondary mb-0">Includes received purchase orders as expenses and delivered sales orders as revenue.</p>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label" for="profitLossStartDate">From</label>
                    <input class="form-control" id="profitLossStartDate" type="date" data-table-filter="start_date">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label" for="profitLossEndDate">To</label>
                    <input class="form-control" id="profitLossEndDate" type="date" data-table-filter="end_date">
                </div>
            </div>
            <div class="table-responsive">
                <table id="profitLossTable" class="table table-hover align-middle w-100" data-source="{{ route('reports.profit-loss.data') }}">
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
            const table = document.getElementById('profitLossTable');
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
            const dataTable = $('#profitLossTable').DataTable({
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
                    emptyTable: 'No profit and loss data is available yet.',
                    zeroRecords: 'No purchase or sales orders match your search or filters.',
                    infoEmpty: 'No purchase or sales orders to display.',
                    infoFiltered: '(filtered from _MAX_ total transaction types)',
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

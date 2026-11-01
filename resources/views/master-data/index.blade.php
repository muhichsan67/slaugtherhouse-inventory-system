@extends('layouts.app')

@section('title', $title)

@push('styles')
    <link href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/css/responsive.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="mb-4 page-head d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="h3 mb-1">{{ $title }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                </ol>
            </nav>
        </div>
        <a class="btn btn-primary" href="{{ route($routeName.'.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Add record
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="masterDataTable" class="table table-hover align-middle w-100"
                    data-source="{{ route($routeName.'.data') }}">
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
            const dataColumns = columns.map(function (column) {
                return {
                    data: column.key,
                    render: column.boolean
                        ? function (value, type) {
                            if (type !== 'display') {
                                return value ? 'Active' : 'Inactive';
                            }

                            const status = value
                                ? { label: 'Active', color: 'success' }
                                : { label: 'Inactive', color: 'secondary' };

                            return '<span class="badge text-bg-' + status.color + '">' + status.label + '</span>';
                        }
                        : column.format === 'number'
                            ? function (value, type) {
                                return type === 'display'
                                    ? window.appFormatNumber(value, column.decimals ?? 2)
                                    : value;
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
                    if (type !== 'display') {
                        return '';
                    }

                    const csrf = document.querySelector('meta[name="csrf-token"]').content;

                    return '<a class="btn btn-sm btn-outline-primary" href="' + record.edit_url + '">' +
                        '<i class="bi bi-pencil me-1"></i>Edit</a> ' +
                        '<form class="d-inline" method="POST" action="' + record.delete_url + '" data-confirm="Delete this record?">' +
                        '<input type="hidden" name="_token" value="' + csrf + '">' +
                        '<input type="hidden" name="_method" value="DELETE">' +
                        '<button class="btn btn-sm btn-outline-danger" type="submit">' +
                        '<i class="bi bi-trash me-1"></i>Delete</button></form>';
                }
            });

            $('#masterDataTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                deferRender: true,
                ajax: {
                    url: document.getElementById('masterDataTable').dataset.source,
                    type: 'GET'
                },
                columns: dataColumns,
                order: [[0, 'asc']],
                language: {
                    emptyTable: 'No {{ strtolower($title) }} data is available yet.',
                    zeroRecords: 'No {{ strtolower($title) }} match your search.',
                    infoEmpty: 'No {{ strtolower($title) }} to display.',
                    infoFiltered: '(filtered from _MAX_ total {{ strtolower($title) }})',
                    processing: 'Loading records...'
                }
            });
        });
    </script>
@endpush

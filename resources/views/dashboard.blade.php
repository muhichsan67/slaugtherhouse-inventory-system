@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-4 page-head">
        <h1 class="h3 mb-1">Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active" aria-current="page">Home</li>
            </ol>
        </nav>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <h2 class="h5 mb-1">Welcome, {{ $user->name }}</h2>
                        <p class="text-secondary mb-0">
                            Your inventory and reporting workspace is ready. Use the navigation to access available areas.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-icon bg-success-subtle text-success mb-3"><i class="bi bi-boxes"></i></div>
                    <h2 class="h5">Inventory</h2>
                    <p class="text-secondary mb-0">Stock tracking and warehouse operations will be available here.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-icon bg-warning-subtle text-warning mb-3"><i class="bi bi-arrow-left-right"></i></div>
                    <h2 class="h5">Purchasing &amp; sales</h2>
                    <p class="text-secondary mb-0">Purchase and sales workflows will appear here as they are implemented.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stat-icon bg-info-subtle text-info mb-3"><i class="bi bi-bar-chart-line"></i></div>
                    <h2 class="h5">Reports</h2>
                    <p class="text-secondary mb-0">Finance and Admin users can access Profit &amp; Loss and Stock Card reports.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

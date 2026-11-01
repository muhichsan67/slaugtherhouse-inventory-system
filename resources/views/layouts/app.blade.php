<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Slaughterhouse Inventory</title>
    <link href="{{ asset('vendor/inter/inter.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/admin.css') }}" rel="stylesheet">
    @stack('styles')
    <script>
        try {
            document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('nimbus-theme') || 'light');
        } catch (error) {}
    </script>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <i class="bi bi-box-seam"></i> Slaughterhouse
        </a>
        <nav class="nav-scroll nav flex-column" aria-label="Main navigation">
            <div class="nav-heading">Main</div>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2"></i>Dashboard
            </a>
            @if ($user->hasRole('purchasing', 'warehouse', 'sales', 'admin'))
                <div class="nav-heading">Transactions</div>
            @endif
            @if ($user->hasRole('purchasing', 'admin'))
                <a class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
                    <i class="bi bi-cart-plus"></i>Purchase Orders
                </a>
            @endif
            @if ($user->hasRole('sales', 'admin'))
                <a class="nav-link {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}" href="{{ route('sales-orders.index') }}">
                    <i class="bi bi-receipt"></i>Sales Orders
                </a>
            @endif
            @if ($user->hasRole('warehouse', 'admin'))
                <a class="nav-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}" href="{{ route('receipts.index') }}">
                    <i class="bi bi-box-arrow-in-down"></i>Receipts
                </a>
                <a class="nav-link {{ request()->routeIs('deliveries.*') ? 'active' : '' }}" href="{{ route('deliveries.index') }}">
                    <i class="bi bi-box-arrow-up-right"></i>Deliveries
                </a>
                <a class="nav-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}" href="{{ route('stocks.index') }}">
                    <i class="bi bi-boxes"></i>Stock Balances
                </a>
            @endif
            @if ($user->hasRole('finance', 'admin'))
                <div class="nav-heading">Reports</div>
                <a class="nav-link {{ request()->routeIs('reports.profit-loss*') ? 'active' : '' }}" href="{{ route('reports.profit-loss') }}">
                    <i class="bi bi-graph-up"></i>Profit &amp; Loss
                </a>
                <a class="nav-link {{ request()->routeIs('reports.stock-card*') ? 'active' : '' }}" href="{{ route('reports.stock-card') }}">
                    <i class="bi bi-card-list"></i>Stock Card
                </a>
            @endif
            @if ($user->hasRole('admin'))
                <div class="nav-heading">Master Data</div>
                <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                    <i class="bi bi-tags"></i>Categories
                </a>
                <a class="nav-link {{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}">
                    <i class="bi bi-rulers"></i>Units
                </a>
                <a class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">
                    <i class="bi bi-building"></i>Warehouses
                </a>
                <a class="nav-link {{ request()->routeIs('partners.*') ? 'active' : '' }}" href="{{ route('partners.index') }}">
                    <i class="bi bi-people"></i>Partners
                </a>
                <a class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}" href="{{ route('items.index') }}">
                    <i class="bi bi-boxes"></i>Items
                </a>
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-person-gear"></i>Users
                </a>
            @endif
        </nav>
    </aside>
    <div class="sidebar-backdrop"></div>
    <div class="main">
        <header class="topbar">
            <button class="btn btn-light border" data-sidebar-toggle aria-label="Toggle sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn btn-light border" data-theme-toggle aria-label="Toggle theme">
                    <i data-theme-icon class="bi bi-moon-stars"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-light border d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar" style="width:28px;height:28px">{{ str($user->name)->substr(0, 1)->upper() }}</span>
                        <span class="d-none d-sm-inline">{{ $user->name }}</span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sign out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        <main class="content p-3 p-md-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->has('business'))
                <div class="alert alert-danger" role="alert">
                    {{ $errors->first('business') }}
                </div>
            @endif
            @yield('content')
        </main>
        <footer class="px-4 py-3 border-top small text-secondary d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; {{ now()->year }} Slaughterhouse Inventory</span>
            <span>Inventory &amp; Simple Ledger</span>
        </footer>
    </div>
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/admin.js') }}"></script>
    @stack('scripts')
</body>
</html>

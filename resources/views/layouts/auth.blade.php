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
    @yield('content')

    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/admin.js') }}"></script>
    @stack('scripts')
</body>
</html>

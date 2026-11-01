@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <div class="auth-wrap">
        <div class="card auth-card">
            <div class="row g-0">
                <div class="col-lg-5 auth-side d-none d-lg-flex flex-column justify-content-between">
                    <div class="fs-4 fw-bold"><i class="bi bi-box-seam me-2"></i>Slaughterhouse</div>
                    <div>
                        <h2 class="fw-bold">Welcome back</h2>
                        <p class="opacity-75">Manage inventory and daily operations from one place.</p>
                    </div>
                    <small class="opacity-75">&copy; {{ now()->year }} Slaughterhouse Inventory</small>
                </div>
                <div class="col-lg-7">
                    <div class="p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h3 class="fw-bold mb-1">Sign in</h3>
                                <p class="text-secondary mb-0">Enter your credentials to continue.</p>
                            </div>
                            <button class="btn btn-light border" data-theme-toggle type="button" aria-label="Toggle theme">
                                <i data-theme-icon class="bi bi-moon-stars"></i>
                            </button>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="email">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input
                                        type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        autocomplete="username"
                                        autofocus
                                        placeholder="you@example.com"
                                    >
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        id="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="Enter your password"
                                    >
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <button class="btn btn-outline-secondary" type="button" data-toggle-password aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <button class="btn btn-primary w-100 py-2" type="submit">Sign in</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Store Manager')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="app-page">
        <aside class="app-sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand__mark">M</span>
                <span>
                    <span class="brand__name">Store Manager</span>
                    <span class="brand__tagline">Counter operations</span>
                </span>
            </a>

            <nav class="site-nav" aria-label="Main navigation">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Dashboard</a>
                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') && !request()->routeIs('products.low-stock') ? 'is-active' : '' }}">Products</a>
                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'is-active' : '' }}">Customers</a>
                <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.index') || request()->routeIs('orders.show') ? 'is-active' : '' }}">Orders</a>
                <a href="{{ route('orders.create') }}" class="{{ request()->routeIs('orders.create') ? 'is-active' : '' }}">Create Order</a>
                <a href="{{ route('products.low-stock') }}" class="{{ request()->routeIs('products.low-stock') ? 'is-active' : '' }}">Low Stock</a>
            </nav>

            <div class="app-sidebar__footer">
                <span class="pill pill--quiet">Laravel mini system</span>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="button button--soft button--full">Logout</button>
                    </form>
                @endauth
            </div>
        </aside>

        <div class="app-main">
            @isset($header)
                <div class="page-shell page-heading">{{ $header }}</div>
            @endisset

            <main class="page-shell main-content">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>

            <footer class="site-footer">
                <div class="page-shell site-footer__inner">
                    <span>Products, orders and stock kept in sync.</span>
                    <span>Built with Laravel.</span>
                </div>
            </footer>
        </div>
    </div>

    @vite('resources/js/app.js')
    @stack('scripts')
</body>
</html>

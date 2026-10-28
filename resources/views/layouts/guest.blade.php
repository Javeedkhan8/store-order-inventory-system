<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Store Manager') }}</title>
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body class="auth-page">
        <main class="auth-shell">
            <a class="auth-brand" href="{{ route('login') }}">
                <span class="brand__mark">M</span>
                <span>
                    <span class="brand__name">Store Manager</span>
                    <span class="brand__tagline">Counter operations</span>
                </span>
            </a>
            <section class="auth-panel">
                {{ $slot }}
            </section>
            <p class="auth-caption">Products, orders and stock kept in sync.</p>
        </main>
        @vite('resources/js/app.js')
    </body>
</html>

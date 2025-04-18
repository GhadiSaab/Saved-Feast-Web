<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'SavedFeast') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- Scripts and CSS -->
    @viteReactRefresh {{-- Added for React Fast Refresh in dev mode --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.jsx'])
</head>
<body class="antialiased">
    <div id="app">
        <!-- React application will mount here -->
    </div>
</body>
</html>

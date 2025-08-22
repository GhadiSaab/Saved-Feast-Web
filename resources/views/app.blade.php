<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SavedFeast - Save food, save money, save the planet. Discover delicious meals from local restaurants at amazing prices while helping reduce food waste.">
    <meta name="keywords" content="food waste, sustainable eating, local restaurants, discounted meals, food saving">
    <meta name="author" content="SavedFeast">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ config('app.name', 'SavedFeast') }}">
    <meta property="og:description" content="Save food, save money, save the planet. Discover delicious meals from local restaurants at amazing prices.">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ config('app.name', 'SavedFeast') }}">
    <meta property="twitter:description" content="Save food, save money, save the planet. Discover delicious meals from local restaurants at amazing prices.">
    <meta property="twitter:image" content="{{ asset('images/og-image.jpg') }}">

    <title>{{ config('app.name', 'SavedFeast') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">

    <!-- Preconnect to external domains for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'SavedFeast'))</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body>
    <div id="app">
        <!-- Header -->
        <header>
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container">
                    <a class="navbar-brand" href="{{ url('/') }}">
                        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" height="40">
                    </a>
                    
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <!-- Left Side Of Navbar -->
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('search') ? 'active' : '' }}" href="{{ route('search') }}">Find Meals</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About Us</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a>
                            </li>
                        </ul>

                        <!-- Right Side Of Navbar -->
                        <ul class="navbar-nav ms-auto">
                            <!-- Authentication Links -->
                            @guest
                                @if (Route::has('login'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                    </li>
                                @endif

                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                    </li>
                                @endif
                            @else
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                        {{ Auth::user()->name }}
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        @if(Auth::user()->isProvider())
                                            <a class="dropdown-item" href="{{ route('provider.dashboard') }}">
                                                <i class="bi bi-speedometer2 me-2"></i>{{ __('Dashboard') }}
                                            </a>
                                            <a class="dropdown-item" href="{{ route('provider.meals.index') }}">
                                                <i class="bi bi-basket me-2"></i>{{ __('Manage Meals') }}
                                            </a>
                                            <a class="dropdown-item" href="{{ route('orders.index') }}">
                                                <i class="bi bi-bag me-2"></i>{{ __('Manage Orders') }}
                                            </a>
                                        @else
                                            <a class="dropdown-item" href="{{ route('orders.index') }}">
                                                <i class="bi bi-bag me-2"></i>{{ __('My Orders') }}
                                            </a>
                                        @endif
                                        
                                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                            <i class="bi bi-person me-2"></i>{{ __('Profile') }}
                                        </a>
                                        
                                        <div class="dropdown-divider"></div>
                                        
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault();
                                                         document.getElementById('logout-form').submit();">
                                            <i class="bi bi-box-arrow-right me-2"></i>{{ __('Logout') }}
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-dark text-white py-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <img src="{{ asset('images/logo-white.png') }}" alt="{{ config('app.name') }}" height="40" class="mb-3">
                        <p class="mb-4">SavedFeast helps reduce food waste by connecting consumers with local businesses offering surplus food at discounted prices.</p>
                        <div class="d-flex gap-3">
                            <a href="#" class="text-white"><i class="bi bi-facebook fs-5"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-instagram fs-5"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-twitter fs-5"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-linkedin fs-5"></i></a>
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-lg-2">
                        <h5>Quick Links</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="{{ route('home') }}" class="text-decoration-none text-white-50">Home</a></li>
                            <li class="mb-2"><a href="{{ route('search') }}" class="text-decoration-none text-white-50">Find Meals</a></li>
                            <li class="mb-2"><a href="{{ route('about') }}" class="text-decoration-none text-white-50">About Us</a></li>
                            <li class="mb-2"><a href="{{ route('contact') }}" class="text-decoration-none text-white-50">Contact</a></li>
                        </ul>
                    </div>
                    
                    <div class="col-md-4 col-lg-3">
                        <h5>For Businesses</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="{{ route('register', ['role' => 'provider']) }}" class="text-decoration-none text-white-50">Join as Provider</a></li>
                            <li class="mb-2"><a href="{{ route('how-it-works') }}" class="text-decoration-none text-white-50">How It Works</a></li>
                            <li class="mb-2"><a href="{{ route('faq') }}" class="text-decoration-none text-white-50">FAQ</a></li>
                        </ul>
                    </div>
                    
                    <div class="col-md-4 col-lg-3">
                        <h5>Contact Information</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-geo-alt me-2"></i> Beirut, Lebanon</li>
                            <li class="mb-2"><i class="bi bi-envelope me-2"></i> info@savedfeast.com</li>
                            <li class="mb-2"><i class="bi bi-telephone me-2"></i> +961 1 234 567</li>
                        </ul>
                    </div>
                </div>
                
                <hr class="my-4 border-secondary">
                
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        <p class="mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item"><a href="#" class="text-white-50 text-decoration-none small">Terms of Service</a></li>
                            <li class="list-inline-item ms-3"><a href="#" class="text-white-50 text-decoration-none small">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>

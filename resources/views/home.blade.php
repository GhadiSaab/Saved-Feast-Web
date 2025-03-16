@extends('layouts.app')

@section('title', 'Home - Save Food, Save Money')

@section('content')
<!-- Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="display-4 fw-bold mb-3">Save Food, Save Money, Save the Planet</h1>
                <p class="lead mb-4">Connect with local businesses to purchase surplus food at discounted prices and help reduce food waste.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('search') }}" class="btn btn-light btn-lg">Find Meals</a>
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">Join SavedFeast</a>
                    @endguest
                </div>
            </div>
            <div class="col-lg-6">
                <img src="{{ asset('images/hero-image.jpg') }}" alt="SavedFeast Hero" class="img-fluid rounded-3 shadow">
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-search fs-1"></i>
                        </div>
                        <h4>Find Meals</h4>
                        <p class="text-muted">Browse available meals from local restaurants, cafes, and grocery stores at discounted prices.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-bag-check fs-1"></i>
                        </div>
                        <h4>Order & Pay</h4>
                        <p class="text-muted">Place your order through our platform and pay securely using cash or card.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-geo-alt fs-1"></i>
                        </div>
                        <h4>Pickup & Enjoy</h4>
                        <p class="text-muted">Pick up your order at the specified time and enjoy your delicious meal while helping reduce food waste.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Meals Section -->
@if($featuredMeals->count() > 0)
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Featured Meals</h2>
            <a href="{{ route('search') }}" class="btn btn-outline-primary">View All Meals</a>
        </div>
        
        <div class="row g-4">
            @foreach($featuredMeals as $meal)
                <div class="col-md-6 col-lg-4">
                    @include('meals.partials.meal-card', ['meal' => $meal])
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Categories Section -->
@if($categories->count() > 0)
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Browse By Category</h2>
        
        <div class="row g-4 justify-content-center">
            @foreach($categories as $category)
                <div class="col-6 col-md-3 col-lg-3">
                    <a href="{{ route('search', ['category' => $category->id]) }}" class="text-decoration-none">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body text-center p-4">
                                @if($category->icon)
                                    <i class="bi bi-{{ $category->icon }} text-primary fs-1 mb-3"></i>
                                @else
                                    <i class="bi bi-tag text-primary fs-1 mb-3"></i>
                                @endif
                                <h5 class="mb-2">{{ $category->name }}</h5>
                                <p class="text-muted small mb-0">{{ $category->meals_count }} meals</p>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        
        <div class="text-center mt-4">
            <a href="{{ route('search') }}" class="btn btn-primary">View All Categories</a>
        </div>
    </div>
</section>
@endif

<!-- Featured Providers Section -->
@if($providers->count() > 0)
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Our Featured Partners</h2>
        
        <div class="row g-4 justify-content-center">
            @foreach($providers as $provider)
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="position-relative">
                            <img src="{{ $provider->providerProfile->getCoverImageUrlAttribute() }}" class="card-img-top" alt="{{ $provider->providerProfile->business_name }}" style="height: 150px; object-fit: cover;">
                            <div class="position-absolute bottom-0 start-0 w-100 p-3 text-white" style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $provider->providerProfile->getLogoUrlAttribute() }}" class="rounded-circle border border-2 border-white" width="50" height="50" alt="{{ $provider->providerProfile->business_name }}">
                                    <div class="ms-3">
                                        <h5 class="mb-0">{{ $provider->providerProfile->business_name }}</h5>
                                        <p class="mb-0 small">{{ $provider->providerProfile->business_type }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text">{{ Str::limit($provider->providerProfile->description, 100) }}</p>
                            <div class="d-flex align-items-center text-muted small">
                                <i class="bi bi-geo-alt me-1"></i>
                                <span>{{ $provider->providerProfile->city }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <a href="{{ route('providers.show', $provider) }}" class="btn btn-outline-primary w-100">View Provider</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Benefits Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2>Make a Difference with Every Meal</h2>
            <p class="text-muted">Join our mission to reduce food waste and save money at the same time</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-success mb-3">
                            <i class="bi bi-tree fs-1"></i>
                        </div>
                        <h4>Environmental Impact</h4>
                        <p class="text-muted">By saving food from being wasted, you're helping reduce greenhouse gas emissions and conserving resources.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-warning mb-3">
                            <i class="bi bi-coin fs-1"></i>
                        </div>
                        <h4>Save Money</h4>
                        <p class="text-muted">Get quality food at discounted prices, helping your budget while enjoying delicious meals.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-danger mb-3">
                            <i class="bi bi-heart fs-1"></i>
                        </div>
                        <h4>Support Local Businesses</h4>
                        <p class="text-muted">Help local restaurants, cafes, and shops reduce waste while increasing their revenue.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="fs-1 mb-4">Ready to join the movement?</h2>
                <p class="lead mb-4">Start saving food, money, and the planet today.</p>
                <div class="d-flex justify-content-center gap-3">
                    @guest
                        <a href="{{ route('register', ['role' => 'consumer']) }}" class="btn btn-light btn-lg">Sign Up as Customer</a>
                        <a href="{{ route('register', ['role' => 'provider']) }}" class="btn btn-outline-light btn-lg">Join as Business</a>
                    @else
                        <a href="{{ route('search') }}" class="btn btn-light btn-lg">Browse Meals</a>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Any home page specific JavaScript here
    });
</script>
@endpush

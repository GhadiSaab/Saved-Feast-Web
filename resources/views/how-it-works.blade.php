@extends('layouts.app')

@section('title', 'How It Works | SavedFeast')

@section('content')
<!-- Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4">How SavedFeast Works</h1>
                <p class="lead mb-0">A simple process to save food, save money, and help the planet.</p>
            </div>
        </div>
    </div>
</section>

<!-- Process Steps Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="mb-4">For Customers</h2>
                <p class="lead">Enjoy delicious food at discounted prices while helping reduce food waste.</p>
            </div>
        </div>
        
        <!-- Step 1 -->
        <div class="row align-items-center mb-5">
            <div class="col-lg-6 order-lg-2 mb-4 mb-lg-0">
                <img src="{{ asset('images/step-1.jpg') }}" alt="Step 1" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="d-flex align-items-center mb-4">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; flex-shrink: 0;">
                        <span class="fw-bold">1</span>
                    </div>
                    <h3 class="mb-0">Browse Available Meals</h3>
                </div>
                <p>Explore our marketplace to discover nearby restaurants, cafes, and bakeries offering surplus food at discounted prices. Filter by location, cuisine type, or pickup time to find exactly what you're looking for.</p>
                <p>Each listing shows you:</p>
                <ul>
                    <li>What's included in the meal</li>
                    <li>Original price vs. discounted price</li>
                    <li>Available pickup window</li>
                    <li>Distance from your location</li>
                </ul>
            </div>
        </div>
        
        <!-- Step 2 -->
        <div class="row align-items-center mb-5">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="{{ asset('images/step-2.jpg') }}" alt="Step 2" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center mb-4">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; flex-shrink: 0;">
                        <span class="fw-bold">2</span>
                    </div>
                    <h3 class="mb-0">Place Your Order</h3>
                </div>
                <p>Once you find something you like, simply place your order through our secure platform. Pay online using your preferred payment method, and you'll receive an order confirmation with all the details.</p>
                <p>The process is simple:</p>
                <ul>
                    <li>Select your meal and quantity</li>
                    <li>Check out securely</li>
                    <li>Receive instant confirmation</li>
                    <li>Get reminder notifications before pickup</li>
                </ul>
            </div>
        </div>
        
        <!-- Step 3 -->
        <div class="row align-items-center">
            <div class="col-lg-6 order-lg-2 mb-4 mb-lg-0">
                <img src="{{ asset('images/step-3.jpg') }}" alt="Step 3" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="d-flex align-items-center mb-4">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; flex-shrink: 0;">
                        <span class="fw-bold">3</span>
                    </div>
                    <h3 class="mb-0">Pick Up & Enjoy</h3>
                </div>
                <p>Visit the business during the specified pickup window to collect your meal. Show your order confirmation (in-app or email), grab your food, and enjoy!</p>
                <p>After pickup, you can:</p>
                <ul>
                    <li>Rate your experience</li>
                    <li>Leave a review for the provider</li>
                    <li>Share your experience on social media</li>
                    <li>Track your personal impact on food waste reduction</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Provider Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="mb-4">For Food Providers</h2>
                <p class="lead">Turn your surplus food into additional revenue while making a positive environmental impact.</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mb-4 mx-auto" style="width: 60px; height: 60px;">
                            <i class="bi bi-shop fs-4"></i>
                        </div>
                        <h4 class="card-title text-center mb-3">Register Your Business</h4>
                        <p class="card-text">Sign up as a food provider on our platform. Complete your business profile with details about your establishment, including location, cuisine type, and operating hours.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mb-4 mx-auto" style="width: 60px; height: 60px;">
                            <i class="bi bi-basket fs-4"></i>
                        </div>
                        <h4 class="card-title text-center mb-3">List Your Surplus Food</h4>
                        <p class="card-text">Create listings for your surplus food items. You decide what to offer, when it's available for pickup, and how much to charge (typically 40-70% off the regular price).</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mb-4 mx-auto" style="width: 60px; height: 60px;">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                        <h4 class="card-title text-center mb-3">Get Paid & Reduce Waste</h4>
                        <p class="card-text">Customers order and pay through our platform. You prepare the orders for pickup, hand them over during the specified time window, and receive payment directly to your account.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Join as a Food Provider</a>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="mb-4">Why Use SavedFeast?</h2>
                <p class="lead">Multiple benefits for everyone involved.</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-3 d-flex align-items-center">
                            <i class="bi bi-earth me-3 text-primary"></i>
                            Environmental Impact
                        </h4>
                        <p class="card-text">Each meal saved through our platform prevents food waste and reduces CO2 emissions. By using SavedFeast, you're directly contributing to a more sustainable food system.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-3 d-flex align-items-center">
                            <i class="bi bi-piggy-bank me-3 text-primary"></i>
                            Cost Savings
                        </h4>
                        <p class="card-text">Customers enjoy quality food at significantly discounted prices, while businesses recover costs on items that would otherwise go to waste – creating a win-win economic model.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-3 d-flex align-items-center">
                            <i class="bi bi-building me-3 text-primary"></i>
                            Business Benefits
                        </h4>
                        <p class="card-text">Food providers gain new customers, additional revenue streams, reduced waste costs, and enhance their sustainability credentials – all with minimal extra effort.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-3 d-flex align-items-center">
                            <i class="bi bi-people me-3 text-primary"></i>
                            Community Impact
                        </h4>
                        <p class="card-text">By connecting local businesses with conscious consumers, SavedFeast helps build stronger, more sustainable communities while raising awareness about food waste issues.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h2 class="mb-4">Ready to Get Started?</h2>
        <p class="lead mb-4">Join thousands of users already making a difference through SavedFeast.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('register') }}" class="btn btn-light btn-lg">Create an Account</a>
            <a href="{{ route('search') }}" class="btn btn-outline-light btn-lg">Browse Available Meals</a>
        </div>
    </div>
</section>
@endsection

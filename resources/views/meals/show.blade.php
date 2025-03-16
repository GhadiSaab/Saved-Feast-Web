@extends('layouts.app')

@section('title', $meal->name)

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('search') }}">Meals</a></li>
            @if($meal->category)
                <li class="breadcrumb-item"><a href="{{ route('search', ['category' => $meal->category->id]) }}">{{ $meal->category->name }}</a></li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $meal->name }}</li>
        </ol>
    </nav>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Meal Details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="position-relative">
                        <img src="{{ $meal->getImageUrlAttribute() }}" alt="{{ $meal->name }}" class="w-100" style="max-height: 400px; object-fit: cover;">
                        
                        <div class="position-absolute top-0 end-0 p-3">
                            <span class="badge bg-danger">{{ $meal->discountPercentageAttribute }}% OFF</span>
                        </div>
                        
                        @if(!$meal->isAvailable() && $meal->status !== 'active')
                            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background-color: rgba(0, 0, 0, 0.5);">
                                <span class="badge bg-danger fs-4 px-4 py-2">SOLD OUT</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="p-4">
                        <h2 class="mb-1">{{ $meal->name }}</h2>
                        
                        <div class="d-flex align-items-center mb-3">
                            <a href="{{ route('providers.show', $meal->provider) }}" class="text-decoration-none text-muted">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $meal->provider->providerProfile->getLogoUrlAttribute() }}" class="rounded-circle me-2" width="24" height="24" alt="{{ $meal->provider->providerProfile->business_name }}">
                                    <span>{{ $meal->provider->providerProfile->business_name }}</span>
                                </div>
                            </a>
                            
                            @if($meal->category)
                                <span class="mx-2">•</span>
                                <a href="{{ route('search', ['category' => $meal->category->id]) }}" class="text-decoration-none text-muted">
                                    {{ $meal->category->name }}
                                </a>
                            @endif
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-baseline gap-2">
                                <h4 class="mb-0 text-primary">{{ number_format($meal->discounted_price, 2) }} LBP</h4>
                                <span class="text-muted text-decoration-line-through">{{ number_format($meal->original_price, 2) }} LBP</span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="mb-0">{{ $meal->description }}</p>
                        </div>
                        
                        @if($meal->allergens)
                            <div class="mb-4">
                                <h5>Allergens</h5>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($meal->allergensArrayAttribute as $allergen)
                                        <span class="badge bg-light text-dark">{{ $allergen }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <div class="mb-4">
                            <h5>Availability</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Quantity:</strong> {{ $meal->available_quantity }} left</p>
                                    <p class="mb-0"><strong>Available From:</strong> {{ $meal->available_from->format('M d, Y - H:i') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-0"><strong>Available Until:</strong> {{ $meal->available_until->format('M d, Y - H:i') }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex align-items-center mb-4">
                            <h5 class="mb-0">Pickup Location</h5>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-geo-alt text-primary"></i>
                                <span>{{ $meal->provider->providerProfile->address }}, {{ $meal->provider->providerProfile->city }}</span>
                            </div>
                            
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <i class="bi bi-clock text-primary"></i>
                                <span>
                                    @if($meal->provider->providerProfile->opening_time && $meal->provider->providerProfile->closing_time)
                                        Opening Hours: {{ $meal->provider->providerProfile->opening_time->format('H:i') }} - {{ $meal->provider->providerProfile->closing_time->format('H:i') }}
                                    @else
                                        Opening Hours: Not specified
                                    @endif
                                </span>
                            </div>
                            
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <i class="bi bi-telephone text-primary"></i>
                                <span>{{ $meal->provider->providerProfile->phone_number }}</span>
                            </div>
                        </div>
                        
                        @if($meal->provider->providerProfile->latitude && $meal->provider->providerProfile->longitude)
                            <div class="mb-4">
                                <div id="map" style="height: 300px;"></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Box -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body p-4">
                    <h4 class="mb-4">Order This Meal</h4>
                    
                    @guest
                        <div class="alert alert-warning">
                            <p class="mb-2">Please login to order this meal.</p>
                            <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                            <a href="{{ route('register') }}" class="btn btn-outline-primary">Register</a>
                        </div>
                    @else
                        @if($meal->available_quantity > 0 && $meal->status === 'active' && $meal->available_until > now())
                            <form action="{{ route('orders.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="meal_id" value="{{ $meal->id }}">
                                
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <select class="form-select @error('quantity') is-invalid @enderror" id="quantity" name="quantity" required>
                                        @for($i = 1; $i <= min(5, $meal->available_quantity); $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="pickup_time" class="form-label">Pickup Time</label>
                                    <input type="datetime-local" 
                                           class="form-control @error('pickup_time') is-invalid @enderror" 
                                           id="pickup_time" 
                                           name="pickup_time" 
                                           min="{{ now()->format('Y-m-d\TH:i') }}"
                                           max="{{ $meal->available_until->format('Y-m-d\TH:i') }}" 
                                           required>
                                    @error('pickup_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Special Instructions (Optional)</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Payment Method</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" checked>
                                            <label class="form-check-label" for="payment_cash">
                                                Cash on Pickup
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="payment_card" value="card">
                                            <label class="form-check-label" for="payment_card">
                                                Card Payment
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-bag-plus"></i> Place Order
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="alert alert-danger">
                                <h5 class="alert-heading">Meal Unavailable</h5>
                                <p>Sorry, this meal is no longer available for ordering.</p>
                                <hr>
                                <a href="{{ route('search') }}" class="btn btn-outline-danger">Find Other Meals</a>
                            </div>
                        @endif
                    @endguest
                </div>
            </div>
            
            <!-- Provider Info -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h4 class="mb-3">About the Provider</h4>
                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ $meal->provider->providerProfile->getLogoUrlAttribute() }}" 
                             class="rounded-circle me-3" 
                             width="60" height="60" 
                             alt="{{ $meal->provider->providerProfile->business_name }}">
                        <div>
                            <h5 class="mb-0">{{ $meal->provider->providerProfile->business_name }}</h5>
                            <p class="text-muted mb-0">{{ $meal->provider->providerProfile->business_type }}</p>
                        </div>
                    </div>
                    <p>{{ Str::limit($meal->provider->providerProfile->description, 200) }}</p>
                    <a href="{{ route('providers.show', $meal->provider_id) }}" class="btn btn-outline-primary">
                        View Provider Details
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Meals -->
    @if($relatedMeals->count() > 0)
        <div class="mt-5">
            <h3 class="mb-4">Similar Meals You Might Like</h3>
            <div class="row g-4">
                @foreach($relatedMeals as $relatedMeal)
                    <div class="col-md-3">
                        @include('meals.partials.meal-card', ['meal' => $relatedMeal])
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
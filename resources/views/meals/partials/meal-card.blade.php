<div class="card h-100 border-0 shadow-sm hover-lift position-relative">
    @if(!$meal->isAvailable() && $meal->status === 'sold_out')
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background-color: rgba(0, 0, 0, 0.5); z-index: 1;">
            <span class="badge bg-danger fs-5 px-3 py-2">SOLD OUT</span>
        </div>
    @elseif($meal->available_until < now())
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background-color: rgba(0, 0, 0, 0.5); z-index: 1;">
            <span class="badge bg-secondary fs-5 px-3 py-2">EXPIRED</span>
        </div>
    @endif

    <div class="position-relative">
        <img src="{{ $meal->getImageUrlAttribute() }}" class="card-img-top" alt="{{ $meal->name }}" style="height: 180px; object-fit: cover;">
        <div class="position-absolute top-0 start-0 m-3">
            <span class="badge bg-danger">{{ $meal->discountPercentageAttribute }}% OFF</span>
        </div>
        <div class="position-absolute bottom-0 start-0 w-100 p-2 text-white" style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
            <div class="d-flex align-items-center">
                <img src="{{ $meal->provider->providerProfile->getLogoUrlAttribute() }}" class="rounded-circle border border-white border-2 me-2" width="30" height="30" alt="{{ $meal->provider->providerProfile->business_name }}">
                <span>{{ $meal->provider->providerProfile->business_name }}</span>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <h5 class="card-title mb-1">{{ Str::limit($meal->name, 40) }}</h5>
        <p class="text-muted small mb-2">
            <i class="bi bi-shop"></i> {{ $meal->provider->providerProfile->business_name }} · 
            <i class="bi bi-geo-alt"></i> {{ $meal->provider->providerProfile->city }}
        </p>
        <p class="card-text small text-muted mb-3">{{ Str::limit($meal->description, 80) }}</p>
        
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted small text-decoration-line-through">{{ number_format($meal->original_price, 2) }} LBP</span>
                <span class="text-primary fw-bold ms-1">{{ number_format($meal->discounted_price, 2) }} LBP</span>
            </div>
            
            @if($meal->available_quantity > 0)
                <span class="badge bg-success">{{ $meal->available_quantity }} left</span>
            @else
                <span class="badge bg-danger">Sold Out</span>
            @endif
        </div>
        
        <hr class="my-2">
        
        <div class="d-flex justify-content-between align-items-center">
            <span class="badge bg-light text-dark">
                <i class="bi bi-clock"></i> Pickup until {{ $meal->available_until->format('H:i') }}
            </span>
            <a href="{{ route('meals.show', $meal) }}" class="btn btn-sm btn-outline-primary">View Details</a>
        </div>
    </div>
</div>

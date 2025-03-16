@extends('layouts.app')

@section('title', $provider->providerProfile->business_name)

@section('content')
<div class="container py-4">
    <!-- Provider Header -->
    <div class="position-relative mb-4">
        <img src="{{ $provider->providerProfile->getCoverImageUrlAttribute() }}" 
             class="w-100 rounded-3 shadow" 
             style="height: 250px; object-fit: cover;" 
             alt="{{ $provider->providerProfile->business_name }}">
             
        <div class="position-absolute bottom-0 start-0 p-4 text-white w-100" style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
            <div class="d-flex align-items-end">
                <img src="{{ $provider->providerProfile->getLogoUrlAttribute() }}" 
                     class="rounded-circle border border-3 border-white" 
                     width="100" height="100" 
                     alt="{{ $provider->providerProfile->business_name }}">
                     
                <div class="ms-3">
                    <h1 class="mb-1">{{ $provider->providerProfile->business_name }}</h1>
                    <div class="d-flex gap-3">
                        <span><i class="bi bi-shop"></i> {{ $provider->providerProfile->business_type }}</span>
                        <span><i class="bi bi-geo-alt"></i> {{ $provider->providerProfile->city }}</span>
                        <span><i class="bi bi-telephone"></i> {{ $provider->providerProfile->phone_number }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- About -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="mb-3">About</h3>
                    <p>{{ $provider->providerProfile->description }}</p>
                    
                    <hr>
                    
                    <h5>Location & Hours</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Address:</strong><br>{{ $provider->providerProfile->address }}</p>
                        </div>
                        <div class="col-md-6">
                            <p>
                                <strong>Opening Hours:</strong><br>
                                @if($provider->providerProfile->opening_time && $provider->providerProfile->closing_time)
                                    {{ $provider->providerProfile->opening_time->format('H:i') }} - {{ $provider->providerProfile->closing_time->format('H:i') }}
                                @else
                                    Not specified
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    <!-- Map will be added here -->
                    @if($provider->providerProfile->latitude && $provider->providerProfile->longitude)
                        <div id="map" class="mt-3 rounded" style="height: 300px;"></div>
                    @endif
                </div>
            </div>
            
            <!-- Available Meals -->
            <h3 class="mb-4">Available Meals</h3>
            
            @if($meals->count() > 0)
                <div class="row g-4">
                    @foreach($meals as $meal)
                        <div class="col-md-6">
                            @include('meals.partials.meal-card', ['meal' => $meal])
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-4">
                    {{ $meals->links() }}
                </div>
            @else
                <div class="alert alert-info">
                    <h5 class="alert-heading">No meals available</h5>
                    <p class="mb-0">This provider currently has no active meals. Please check back later.</p>
                </div>
            @endif
        </div>
        
        <div class="col-md-4">
            <!-- Contact Info -->
            <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 20px;">
                <div class="card-body p-4">
                    <h4 class="mb-3">Contact Information</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-geo-alt-fill me-2 text-primary"></i>
                            {{ $provider->providerProfile->address }}, {{ $provider->providerProfile->city }}
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-telephone-fill me-2 text-primary"></i>
                            {{ $provider->providerProfile->phone_number }}
                        </li>
                        <li>
                            <i class="bi bi-envelope-fill me-2 text-primary"></i>
                            {{ $provider->email }}
                        </li>
                    </ul>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="tel:{{ $provider->providerProfile->phone_number }}" class="btn btn-outline-primary">
                            <i class="bi bi-telephone"></i> Call
                        </a>
                        <a href="mailto:{{ $provider->email }}" class="btn btn-outline-primary">
                            <i class="bi bi-envelope"></i> Email
                        </a>
                        <a href="https://maps.google.com/?q={{ $provider->providerProfile->latitude }},{{ $provider->providerProfile->longitude }}" 
                           target="_blank" 
                           class="btn btn-outline-primary">
                            <i class="bi bi-map"></i> Get Directions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($provider->providerProfile->latitude && $provider->providerProfile->longitude)
    @push('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&callback=initMap" defer></script>
    <script>
        function initMap() {
            const position = {
                lat: {{ $provider->providerProfile->latitude }}, 
                lng: {{ $provider->providerProfile->longitude }}
            };
            
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: position,
            });
            
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: "{{ $provider->providerProfile->business_name }}"
            });
        }
    </script>
    @endpush
@endif
@endsection

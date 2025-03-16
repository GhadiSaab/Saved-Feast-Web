@extends('layouts.app')

@section('title', 'Search Meals')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Find Available Meals</h1>
    
    <!-- Search Form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('search') }}" method="GET">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0" name="query" value="{{ request('query') }}" placeholder="Search meals...">
                        </div>
                    </div>
                    
                    <div class="col-lg-3">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <input type="text" class="form-control" name="location" value="{{ request('location') }}" placeholder="Location (city)">
                    </div>
                    
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label fw-semibold mb-0 me-2">Sort by:</label>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="sort" id="sort-newest" value="newest" autocomplete="off" {{ request('sort', 'newest') === 'newest' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary btn-sm" for="sort-newest">Newest</label>
                                    
                                    <input type="radio" class="btn-check" name="sort" id="sort-price_low" value="price_low" autocomplete="off" {{ request('sort') === 'price_low' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary btn-sm" for="sort-price_low">Price (Low-High)</label>
                                    
                                    <input type="radio" class="btn-check" name="sort" id="sort-price_high" value="price_high" autocomplete="off" {{ request('sort') === 'price_high' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary btn-sm" for="sort-price_high">Price (High-Low)</label>
                                    
                                    <input type="radio" class="btn-check" name="sort" id="sort-discount" value="discount" autocomplete="off" {{ request('sort') === 'discount' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary btn-sm" for="sort-discount">Highest Discount</label>
                                </div>
                            </div>
                            
                            <div class="text-muted">
                                {{ $meals->total() }} meals found
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Results -->
    @if($meals->count() > 0)
        <div class="row g-4">
            @foreach($meals as $meal)
                <div class="col-md-6 col-lg-4">
                    @include('meals.partials.meal-card', ['meal' => $meal])
                </div>
            @endforeach
        </div>
        
        <div class="mt-4 d-flex justify-content-center">
            {{ $meals->links() }}
        </div>
    @else
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="bi bi-search display-1 text-muted"></i>
            </div>
            <h3>No meals found</h3>
            <p class="text-muted mb-4">Try adjusting your search criteria or check back later for new meals.</p>
            <a href="{{ route('search') }}" class="btn btn-primary">Clear Filters</a>
        </div>
    @endif
</div>
@endsection

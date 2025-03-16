@extends('layouts.app')

@section('title', 'Edit Meal')

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('provider.meals.index') }}">Meals</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Meal</li>
        </ol>
    </nav>
    
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Meal: {{ $meal->name }}</h5>
                
                <div class="d-flex gap-2">
                    <a href="{{ route('meals.show', $meal) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View Meal
                    </a>
                    
                    <form action="{{ route('provider.meals.destroy', $meal) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this meal? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i> Delete Meal
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('provider.meals.update', $meal) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row g-4">
                    <div class="col-lg-8">
                        <!-- Basic Info -->
                        <h6 class="fw-bold mb-3">Basic Information</h6>
                        <div class="mb-4">
                            <label for="name" class="form-label">Meal Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $meal->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $meal->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $meal->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label d-block">Allergens (optional)</label>
                            <div class="row g-2">
                                @php
                                    $allergensList = ['Gluten', 'Dairy', 'Nuts', 'Eggs', 'Soy', 'Fish', 'Shellfish', 'Peanuts'];
                                    $mealAllergens = old('allergens', $meal->allergensArrayAttribute);
                                @endphp
                                
                                @foreach($allergensList as $allergen)
                                    <div class="col-md-3 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="allergen_{{ Str::slug($allergen) }}" 
                                                   name="allergens[]" value="{{ $allergen }}" 
                                                   {{ in_array($allergen, $mealAllergens) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allergen_{{ Str::slug($allergen) }}">
                                                {{ $allergen }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('allergens')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Pricing -->
                        <h6 class="fw-bold mb-3">Pricing and Availability</h6>
                        
                        <div class="mb-4">
                            <label for="original_price" class="form-label">Original Price (LBP)</label>
                            <input type="number" step="0.01" class="form-control @error('original_price') is-invalid @enderror" id="original_price" name="original_price" value="{{ old('original_price', $meal->original_price) }}" required>
                            @error('original_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="discounted_price" class="form-label">Discounted Price (LBP)</label>
                            <input type="number" step="0.01" class="form-control @error('discounted_price') is-invalid @enderror" id="discounted_price" name="discounted_price" value="{{ old('discounted_price', $meal->discounted_price) }}" required>
                            @error('discounted_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="discount-percentage" class="form-text">Discount: <span>{{ $meal->discountPercentageAttribute }}</span>%</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="available_quantity" class="form-label">Available Quantity</label>
                            <input type="number" min="1" class="form-control @error('available_quantity') is-invalid @enderror" id="available_quantity" name="available_quantity" value="{{ old('available_quantity', $meal->available_quantity) }}" required>
                            @error('available_quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="available_from" class="form-label">Available From</label>
                            <input type="datetime-local" class="form-control @error('available_from') is-invalid @enderror" id="available_from" name="available_from" value="{{ old('available_from', $meal->available_from->format('Y-m-d\TH:i')) }}" required>
                            @error('available_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="available_until" class="form-label">Available Until</label>
                            <input type="datetime-local" class="form-control @error('available_until') is-invalid @enderror" id="available_until" name="available_until" value="{{ old('available_until', $meal->available_until->format('Y-m-d\TH:i')) }}" required>
                            @error('available_until')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Image Upload -->
                        <div class="mb-4">
                            <label for="image" class="form-label">Meal Image</label>
                            @if($meal->image)
                                <div class="mb-2">
                                    <img src="{{ $meal->getImageUrlAttribute() }}" alt="{{ $meal->name }}" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            @endif
                            <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave empty to keep current image. Recommended: square image (1:1 ratio), max 2MB</div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $meal->is_featured) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">
                                    Feature this meal on homepage
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" {{ old('status', $meal->status) == 'active' ? 'selected' : '' }}>Active (Live)</option>
                                <option value="draft" {{ old('status', $meal->status) == 'draft' ? 'selected' : '' }}>Draft (Hidden)</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="border-top pt-4 mt-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('provider.meals.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Calculate and display discount percentage
    const originalPrice = document.getElementById('original_price');
    const discountedPrice = document.getElementById('discounted_price');
    const discountPercentage = document.querySelector('#discount-percentage span');
    
    function updateDiscount() {
        const original = parseFloat(originalPrice.value) || 0;
        const discounted = parseFloat(discountedPrice.value) || 0;
        
        if (original > 0 && discounted > 0 && discounted < original) {
            const discount = Math.round(((original - discounted) / original) * 100);
            discountPercentage.textContent = discount;
        } else {
            discountPercentage.textContent = '0';
        }
    }
    
    originalPrice.addEventListener('input', updateDiscount);
    discountedPrice.addEventListener('input', updateDiscount);
    
    // Initial calculation
    document.addEventListener('DOMContentLoaded', updateDiscount);
</script>
@endpush
@endsection

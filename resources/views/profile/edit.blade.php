@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Edit Profile</h1>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="row">
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    @if($user->isConsumer())
                        <img src="{{ $user->profile ? $user->profile->getProfileImageUrlAttribute() : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                             class="rounded-circle img-thumbnail mb-3"
                             alt="{{ $user->name }}"
                             width="120" height="120">
                    @else
                        <img src="{{ $user->providerProfile->getLogoUrlAttribute() }}"
                             class="rounded-circle img-thumbnail mb-3"
                             alt="{{ $user->providerProfile->business_name }}"
                             width="120" height="120">
                    @endif
                    
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted">{{ ucfirst($user->role) }}</p>
                    
                    @if($user->isProvider())
                        <div class="badge bg-{{ $user->providerProfile->is_verified ? 'success' : 'warning' }} mb-3">
                            {{ $user->providerProfile->is_verified ? 'Verified Provider' : 'Pending Verification' }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                                Profile Information
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                                Change Password
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="profileTabsContent">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                
                                <!-- Common User Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="form-group">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                @if($user->isConsumer())
                                    <!-- Consumer Profile Fields -->
                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group">
                                                <label for="phone_number" class="form-label">Phone Number</label>
                                                <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $user->profile ? $user->profile->phone_number : '') }}">
                                                @error('phone_number')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="city" class="form-label">City</label>
                                                <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $user->profile ? $user->profile->city : '') }}">
                                                @error('city')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $user->profile ? $user->profile->address : '') }}</textarea>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="profile_image" class="form-label">Profile Image</label>
                                        <input type="file" class="form-control @error('profile_image') is-invalid @enderror" id="profile_image" name="profile_image" accept="image/*">
                                        @error('profile_image')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Upload a square image for best results. Maximum size: 2MB</div>
                                    </div>
                                @else
                                    <!-- Provider Profile Fields -->
                                    <h5 class="mt-4 mb-3">Business Information</h5>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group">
                                                <label for="business_name" class="form-label">Business Name</label>
                                                <input type="text" class="form-control @error('business_name') is-invalid @enderror" id="business_name" name="business_name" value="{{ old('business_name', $user->providerProfile->business_name) }}" required>
                                                @error('business_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="business_type" class="form-label">Business Type</label>
                                                <select class="form-select @error('business_type') is-invalid @enderror" id="business_type" name="business_type" required>
                                                    <option value="">Select type</option>
                                                    <option value="Restaurant" {{ old('business_type', $user->providerProfile->business_type) == 'Restaurant' ? 'selected' : '' }}>Restaurant</option>
                                                    <option value="Cafe" {{ old('business_type', $user->providerProfile->business_type) == 'Cafe' ? 'selected' : '' }}>Cafe</option>
                                                    <option value="Bakery" {{ old('business_type', $user->providerProfile->business_type) == 'Bakery' ? 'selected' : '' }}>Bakery</option>
                                                    <option value="Hotel" {{ old('business_type', $user->providerProfile->business_type) == 'Hotel' ? 'selected' : '' }}>Hotel</option>
                                                    <option value="Supermarket" {{ old('business_type', $user->providerProfile->business_type) == 'Supermarket' ? 'selected' : '' }}>Supermarket</option>
                                                </select>
                                                @error('business_type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="description" class="form-label">Business Description</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $user->providerProfile->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group">
                                                <label for="address" class="form-label">Address</label>
                                                <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $user->providerProfile->address) }}" required>
                                                @error('address')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="city" class="form-label">City</label>
                                                <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $user->providerProfile->city) }}" required>
                                                @error('city')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group">
                                                <label for="phone_number" class="form-label">Phone Number</label>
                                                <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $user->providerProfile->phone_number) }}" required>
                                                @error('phone_number')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="opening_time" class="form-label">Opening Time</label>
                                                        <input type="time" class="form-control @error('opening_time') is-invalid @enderror" id="opening_time" name="opening_time" value="{{ old('opening_time', $user->providerProfile->opening_time ? $user->providerProfile->opening_time->format('H:i') : '') }}">
                                                        @error('opening_time')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label for="closing_time" class="form-label">Closing Time</label>
                                                        <input type="time" class="form-control @error('closing_time') is-invalid @enderror" id="closing_time" name="closing_time" value="{{ old('closing_time', $user->providerProfile->closing_time ? $user->providerProfile->closing_time->format('H:i') : '') }}">
                                                        @error('closing_time')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group">
                                                <label for="logo" class="form-label">Business Logo</label>
                                                <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" accept="image/*">
                                                @error('logo')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Upload a square logo for best results (aspect ratio 1:1). Max size: 2MB</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="cover_image" class="form-label">Cover Image</label>
                                                <input type="file" class="form-control @error('cover_image') is-invalid @enderror" id="cover_image" name="cover_image" accept="image/*">
                                                @error('cover_image')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Recommended aspect ratio 3:1 (landscape). Max size: 2MB</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                            <form action="{{ route('profile.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="mb-4">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-4">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control @error('new_password') is-invalid @enderror" id="new_password" name="new_password" required>
                                    @error('new_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-4">
                                    <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
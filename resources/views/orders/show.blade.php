@extends('layouts.app')

@section('title', 'Order Details - ' . $order->order_number)

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">{{ Auth::user()->isProvider() ? 'Manage Orders' : 'My Orders' }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Order #{{ $order->order_number }}</li>
        </ol>
    </nav>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Order Details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order Details</h5>
                    
                    @if($order->status == 'pending')
                        <span class="badge bg-warning">Pending</span>
                    @elseif($order->status == 'confirmed')
                        <span class="badge bg-primary">Confirmed</span>
                    @elseif($order->status == 'completed')
                        <span class="badge bg-success">Completed</span>
                    @elseif($order->status == 'cancelled')
                        <span class="badge bg-danger">Cancelled</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h6 class="mb-2">Order Information</h6>
                            <p class="mb-1"><strong>Order Number:</strong> {{ $order->order_number }}</p>
                            <p class="mb-1"><strong>Date:</strong> {{ $order->created_at->format('M d, Y - H:i') }}</p>
                            <p class="mb-1"><strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}</p>
                            <p class="mb-0"><strong>Payment Status:</strong> 
                                @if($order->payment_status == 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @else
                                    <span class="badge bg-warning">{{ ucfirst($order->payment_status) }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-2">Pickup Information</h6>
                            <p class="mb-1"><strong>Pickup Time:</strong> {{ $order->pickup_time->format('M d, Y - H:i') }}</p>
                            <p class="mb-1"><strong>Pickup Location:</strong> {{ $order->provider->providerProfile->address }}</p>
                            <p class="mb-0"><strong>Phone:</strong> {{ $order->provider->providerProfile->phone_number }}</p>
                        </div>
                    </div>
                    
                    @if($order->notes)
                        <div class="mb-4">
                            <h6 class="mb-2">Special Instructions</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $order->notes }}
                            </div>
                        </div>
                    @endif
                    
                    <h6 class="mb-3">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $item->meal->getImageUrlAttribute() }}" 
                                                     class="rounded me-3" 
                                                     width="50" height="50" 
                                                     alt="{{ $item->meal->name }}"
                                                     style="object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0">{{ $item->meal->name }}</h6>
                                                    @if($item->meal->category)
                                                        <span class="text-muted small">{{ $item->meal->category->name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ number_format($item->price, 2) }} LBP</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td class="text-end">{{ number_format($item->price * $item->quantity, 2) }} LBP</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                    <td class="text-end"><strong>{{ number_format($order->total_amount, 2) }} LBP</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Order Timeline -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Order Timeline</h5>
                </div>
                <div class="card-body p-4">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Order Placed</h6>
                                <p class="text-muted small mb-0">{{ $order->created_at->format('M d, Y - H:i') }}</p>
                            </div>
                        </div>
                        
                        @if($order->status != 'pending')
                            <div class="timeline-item">
                                <div class="timeline-marker {{ $order->status == 'cancelled' ? 'bg-danger' : 'bg-primary' }}"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">{{ $order->status == 'cancelled' ? 'Order Cancelled' : 'Order Confirmed' }}</h6>
                                    <p class="text-muted small mb-0">{{ $order->updated_at->format('M d, Y - H:i') }}</p>
                                </div>
                            </div>
                        @endif
                        
                        @if($order->status == 'completed')
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Order Completed</h6>
                                    <p class="text-muted small mb-0">{{ $order->updated_at->format('M d, Y - H:i') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Action Card -->
            <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body p-4">
                    @if(Auth::user()->isProvider())
                        @if($order->status == 'pending')
                            <form action="{{ route('provider.orders.update-status', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="btn btn-success w-100 mb-3">
                                    <i class="bi bi-check-circle me-2"></i> Confirm Order
                                </button>
                            </form>
                            
                            <form action="{{ route('provider.orders.update-status', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to cancel this order?')">
                                    <i class="bi bi-x-circle me-2"></i> Cancel Order
                                </button>
                            </form>
                        @elseif($order->status == 'confirmed')
                            <form action="{{ route('provider.orders.update-status', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn btn-success w-100 mb-3">
                                    <i class="bi bi-check-circle me-2"></i> Mark as Completed
                                </button>
                            </form>
                            
                            <form action="{{ route('provider.orders.update-status', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to cancel this order?')">
                                    <i class="bi bi-x-circle me-2"></i> Cancel Order
                                </button>
                            </form>
                        @else
                            <div class="alert {{ $order->status == 'completed' ? 'alert-success' : 'alert-danger' }} mb-0">
                                <p class="mb-0">This order is {{ $order->status }}. No further actions required.</p>
                            </div>
                        @endif
                    @else
                        @if($order->status == 'pending')
                            <form action="{{ route('orders.cancel', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to cancel this order?')">
                                    <i class="bi bi-x-circle me-2"></i> Cancel Order
                                </button>
                            </form>
                        @else
                            <div class="alert {{ $order->status == 'cancelled' ? 'alert-danger' : 'alert-info' }} mb-0">
                                <p class="mb-0">This order is {{ $order->status }}. No further actions required.</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            
            <!-- Provider/Customer Info -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">{{ Auth::user()->isProvider() ? 'Customer Information' : 'Provider Information' }}</h5>
                </div>
                <div class="card-body p-4">
                    @if(Auth::user()->isProvider())
                        <p class="mb-1"><strong>Name:</strong> {{ $order->user->name }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $order->user->email }}</p>
                        
                        @if($order->user->profile)
                            @if($order->user->profile->phone_number)
                                <p class="mb-1"><strong>Phone:</strong> {{ $order->user->profile->phone_number }}</p>
                            @endif
                        @endif
                    @else
                        <div class="text-center mb-3">
                            <img src="{{ $order->provider->providerProfile->getLogoUrlAttribute() }}" class="rounded-circle mb-3" width="80" height="80" alt="{{ $order->provider->providerProfile->business_name }}">
                            <h5>{{ $order->provider->providerProfile->business_name }}</h5>
                            <p class="text-muted mb-0">{{ $order->provider->providerProfile->business_type }}</p>
                        </div>
                        
                        <hr>
                        
                        <p class="mb-1"><strong>Address:</strong> {{ $order->provider->providerProfile->address }}</p>
                        <p class="mb-1"><strong>City:</strong> {{ $order->provider->providerProfile->city }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $order->provider->providerProfile->phone_number }}</p>
                        
                        <hr>
                        
                        <div class="d-grid">
                            <a href="{{ route('providers.show', $order->provider) }}" class="btn btn-outline-primary">
                                <i class="bi bi-shop me-2"></i> View Provider
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 25px;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    .timeline-marker {
        position: absolute;
        left: -30px;
        top: 0;
        width: 15px;
        height: 15px;
        border-radius: 50%;
    }
    .timeline-item:not(:last-child) .timeline-marker::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 15px;
        bottom: -25px;
        width: 2px;
        margin-left: -1px;
        background-color: #e9ecef;
    }
</style>
@endpush
@endsection

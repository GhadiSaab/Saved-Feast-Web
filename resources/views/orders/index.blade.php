@extends('layouts.app')

@section('title', Auth::user()->isProvider() ? 'Manage Orders' : 'My Orders')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">{{ Auth::user()->isProvider() ? 'Manage Orders' : 'My Orders' }}</h1>
    
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
    
    <!-- Order Filter Tabs -->
    <ul class="nav nav-tabs mb-4" id="ordersTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-orders" type="button" role="tab" aria-controls="all-orders" aria-selected="true">
                All Orders
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-orders" type="button" role="tab" aria-controls="pending-orders" aria-selected="false">
                Pending
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="confirmed-tab" data-bs-toggle="tab" data-bs-target="#confirmed-orders" type="button" role="tab" aria-controls="confirmed-orders" aria-selected="false">
                Confirmed
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-orders" type="button" role="tab" aria-controls="completed-orders" aria-selected="false">
                Completed
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled-orders" type="button" role="tab" aria-controls="cancelled-orders" aria-selected="false">
                Cancelled
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="ordersTabContent">
        <!-- All Orders Tab -->
        <div class="tab-pane fade show active" id="all-orders" role="tabpanel" aria-labelledby="all-tab">
            @include('orders.partials.orders-table', ['orders' => $orders])
        </div>
        
        <!-- Pending Orders Tab -->
        <div class="tab-pane fade" id="pending-orders" role="tabpanel" aria-labelledby="pending-tab">
            @include('orders.partials.orders-table', ['orders' => $orders->where('status', 'pending')])
        </div>
        
        <!-- Confirmed Orders Tab -->
        <div class="tab-pane fade" id="confirmed-orders" role="tabpanel" aria-labelledby="confirmed-tab">
            @include('orders.partials.orders-table', ['orders' => $orders->where('status', 'confirmed')])
        </div>
        
        <!-- Completed Orders Tab -->
        <div class="tab-pane fade" id="completed-orders" role="tabpanel" aria-labelledby="completed-tab">
            @include('orders.partials.orders-table', ['orders' => $orders->where('status', 'completed')])
        </div>
        
        <!-- Cancelled Orders Tab -->
        <div class="tab-pane fade" id="cancelled-orders" role="tabpanel" aria-labelledby="cancelled-tab">
            @include('orders.partials.orders-table', ['orders' => $orders->where('status', 'cancelled')])
        </div>
    </div>
</div>
@endsection

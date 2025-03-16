@extends('layouts.app')

@section('title', 'Provider Dashboard')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Provider Dashboard</h1>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-muted mb-0">Total Sales</h6>
                        <div class="bg-light rounded-circle p-2">
                            <i class="bi bi-cash text-success"></i>
                        </div>
                    </div>
                    <h3 class="mb-0">{{ number_format($totalSales, 2) }} LBP</h3>
                    <div class="text-success small">
                        <i class="bi bi-graph-up"></i> Revenue from confirmed/completed orders
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-muted mb-0">Total Orders</h6>
                        <div class="bg-light rounded-circle p-2">
                            <i class="bi bi-bag text-primary"></i>
                        </div>
                    </div>
                    <h3 class="mb-0">{{ $totalOrders }}</h3>
                    <div class="text-primary small">
                        <i class="bi bi-cart-check"></i> All time order count
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-muted mb-0">Pending Orders</h6>
                        <div class="bg-light rounded-circle p-2">
                            <i class="bi bi-hourglass-split text-warning"></i>
                        </div>
                    </div>
                    <h3 class="mb-0">{{ $pendingOrders }}</h3>
                    <div class="text-warning small">
                        <i class="bi bi-clock"></i> Awaiting your confirmation
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-muted mb-0">Active Meals</h6>
                        <div class="bg-light rounded-circle p-2">
                            <i class="bi bi-basket text-info"></i>
                        </div>
                    </div>
                    <h3 class="mb-0">{{ $activeMeals }}</h3>
                    <div class="text-info small">
                        <i class="bi bi-tag"></i> Currently available meals
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Sales Chart -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Sales & Orders (Last 7 Days)</h5>
                </div>
                <div class="card-body p-4">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    @if($recentOrders->count() > 0)
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentOrders as $order)
                                        <tr>
                                            <td>{{ $order->order_number }}</td>
                                            <td>{{ $order->user->name }}</td>
                                            <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                                            <td>{{ number_format($order->total_amount, 2) }} LBP</td>
                                            <td>
                                                @if($order->status === 'pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                @elseif($order->status === 'confirmed')
                                                    <span class="badge bg-info">Confirmed</span>
                                                @elseif($order->status === 'completed')
                                                    <span class="badge bg-success">Completed</span>
                                                @elseif($order->status === 'cancelled')
                                                    <span class="badge bg-danger">Cancelled</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                                    Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            You don't have any orders yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="{{ route('provider.meals.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add New Meal
                        </a>
                        <a href="{{ route('provider.meals.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-list"></i> Manage Meals
                        </a>
                        <a href="{{ route('orders.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-receipt"></i> View Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        const labels = @json($lastSevenDays->pluck('date'));
        const salesData = @json($lastSevenDays->pluck('sales'));
        const ordersData = @json($lastSevenDays->pluck('orders'));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales (LBP)',
                    data: salesData,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    yAxisID: 'y',
                }, {
                    label: 'Orders',
                    data: ordersData,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    yAxisID: 'y1',
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Sales (LBP)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
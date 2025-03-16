@if($orders->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>{{ Auth::user()->isProvider() ? 'Customer' : 'Provider' }}</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Pickup Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="text-decoration-none fw-bold">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td>
                            @if(Auth::user()->isProvider())
                                {{ $order->user->name }}
                            @else
                                <div class="d-flex align-items-center">
                                    <img src="{{ $order->provider->providerProfile->getLogoUrlAttribute() }}" 
                                        class="rounded-circle me-2" 
                                        width="30" height="30" 
                                        alt="{{ $order->provider->providerProfile->business_name }}">
                                    <span>{{ $order->provider->providerProfile->business_name }}</span>
                                </div>
                            @endif
                        </td>
                        <td>{{ $order->created_at->format('M d, Y - H:i') }}</td>
                        <td>{{ number_format($order->total_amount, 2) }} LBP</td>
                        <td>
                            @if($order->status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($order->status == 'confirmed')
                                <span class="badge bg-primary">Confirmed</span>
                            @elseif($order->status == 'completed')
                                <span class="badge bg-success">Completed</span>
                            @elseif($order->status == 'cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                            @endif
                        </td>
                        <td>{{ $order->pickup_time->format('M d, Y - H:i') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>

                                @if(Auth::user()->isProvider() && $order->status == 'pending')
                                    <form action="{{ route('provider.orders.update-status', $order) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-check-lg"></i> Confirm
                                        </button>
                                    </form>
                                @endif

                                @if(!Auth::user()->isProvider() && $order->status == 'pending')
                                    <form action="{{ route('orders.cancel', $order) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this order?')">
                                            <i class="bi bi-x-lg"></i> Cancel
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-5">
        <div class="mb-4">
            <i class="bi bi-bag display-1 text-muted"></i>
        </div>
        <h3>No orders found</h3>
        <p class="text-muted mb-4">You don't have any orders in this category yet.</p>
        @if(!Auth::user()->isProvider())
            <a href="{{ route('search') }}" class="btn btn-primary">Browse Meals</a>
        @endif
    </div>
@endif

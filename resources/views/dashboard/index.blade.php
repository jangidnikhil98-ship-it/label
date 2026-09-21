@extends('layouts.app')

@section('title', 'Dashboard - Order & Shipping Label Management')
@section('page-title', 'Dashboard Overview')

@section('content')
<!-- Metrics Grid -->
<div class="row g-3 mb-4">
    <!-- Today's Orders -->
    <div class="col-md-3">
        <a href="{{ route('orders.index', ['period' => 'today']) }}" class="text-decoration-none">
            <div class="card metric-card p-3 border-start border-4 border-primary shadow-sm hover-shadow">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="card-title text-primary font-semibold">Today's Orders</div>
                        <div class="metric-value text-dark">{{ $todaysOrdersCount }}</div>
                        <div class="fs-8 text-muted mt-1"><i class="bi bi-clock-history"></i> Last 3 Days: <span class="fw-bold text-primary">{{ $last3DaysOrdersCount }}</span></div>
                    </div>
                    <div class="fs-1 text-primary"><i class="bi bi-calendar-check-fill"></i></div>
                </div>
            </div>
        </a>
    </div>

    <!-- New Orders -->
    <div class="col-md-3">
        <div class="card metric-card p-3 border-start border-4 border-secondary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title">New Orders</div>
                    <div class="metric-value">{{ $newOrdersCount }}</div>
                </div>
                <div class="fs-1 text-secondary"><i class="bi bi-cart-plus"></i></div>
            </div>
        </div>
    </div>

    <!-- Pending Verification -->
    <div class="col-md-3">
        <div class="card metric-card p-3 border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title">Pending Verify</div>
                    <div class="metric-value">{{ $pendingVerificationCount }}</div>
                </div>
                <div class="fs-1 text-info"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>

    <!-- Accepted Orders -->
    <div class="col-md-3">
        <div class="card metric-card p-3 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title">Accepted</div>
                    <div class="metric-value">{{ $acceptedOrdersCount }}</div>
                </div>
                <div class="fs-1 text-success"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
    </div>

    <!-- Labels Pending -->
    <div class="col-md-3">
        <div class="card metric-card p-3 border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title">Labels Pending</div>
                    <div class="metric-value">{{ $labelsPendingCount }}</div>
                </div>
                <div class="fs-1 text-warning"><i class="bi bi-file-earmark-code"></i></div>
            </div>
        </div>
    </div>

    <!-- Labels Matched -->
    <div class="col-md-3">
        <div class="card metric-card p-3 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title">Labels Matched</div>
                    <div class="metric-value">{{ $labelsMatchedCount }}</div>
                </div>
                <div class="fs-1 text-success"><i class="bi bi-qr-code"></i></div>
            </div>
        </div>
    </div>

    <!-- Ready for Packing -->
    <div class="col-md-3">
        <div class="card metric-card p-3 border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title">Ready Packing</div>
                    <div class="metric-value">{{ $readyPackingCount }}</div>
                </div>
                <div class="fs-1 text-primary"><i class="bi bi-box-seam"></i></div>
            </div>
        </div>
    </div>

    <!-- Packed -->
    <div class="col-md-3">
        <div class="card metric-card p-3 border-start border-4 border-dark">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title">Packed</div>
                    <div class="metric-value">{{ $packedCount }}</div>
                </div>
                <div class="fs-1 text-dark"><i class="bi bi-truck"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Main Tables Section -->
<div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock me-2"></i> Recent Orders</h6>
                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Order Status</th>
                            <th>Label Status</th>
                            <th>Packing</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr>
                                <td class="fw-bold"><a href="{{ route('orders.show', $order->id) }}">{{ $order->order_id }}</a></td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->phone_number }}</td>
                                <td><x-status-badge :status="$order->status" type="order" /></td>
                                <td><x-status-badge :status="$order->label_status" type="label" /></td>
                                <td><x-status-badge :status="$order->packing_status" type="packing" /></td>
                                <td>
                                    <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i> View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No orders found yet. <a href="{{ route('whatsapp.import') }}">Import messages</a> to create orders.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Attention / Unmatched -->
    <div class="col-lg-4">
        <!-- Orders Requiring Attention -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-warning"><i class="bi bi-exclamation-triangle me-2"></i> Requiring Attention</h6>
            </div>
            <div class="list-group list-group-flush">
                @forelse($attentionOrders as $order)
                    <a href="{{ route('orders.show', $order->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-7">{{ $order->order_id }} ({{ $order->customer_name }})</div>
                            <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                        </div>
                        <x-status-badge :status="$order->status" type="order" />
                    </a>
                @empty
                    <div class="p-3 text-center text-muted fs-7">No pending attention items!</div>
                @endforelse
            </div>
        </div>

        <!-- Unmatched Labels -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-danger"><i class="bi bi-qr-code-scan me-2"></i> Unmatched Labels</h6>
                <a href="{{ route('labels.unmatched') }}" class="btn btn-sm btn-link text-danger p-0 fs-7">View All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($unmatchedLabels as $label)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-7">{{ $label->file_name }}</div>
                            <small class="text-danger">Detected ID: {{ $label->detected_order_id ?: 'None' }}</small>
                        </div>
                        <a href="{{ route('labels.view', $label->id) }}" target="_blank" class="btn btn-sm btn-light border"><i class="bi bi-file-earmark-pdf"></i> View</a>
                    </div>
                @empty
                    <div class="p-3 text-center text-muted fs-7">All uploaded labels matched!</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

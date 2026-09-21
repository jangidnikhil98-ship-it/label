@extends('layouts.app')

@section('title', 'Order Reports')
@section('page-title', 'Order Analytics & Multi-Format Exports')

@section('content')
<!-- Quick Date Presets -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('reports.orders', ['period' => 'today']) }}" class="btn btn-sm {{ request('period') === 'today' ? 'btn-primary' : 'btn-outline-primary' }}"><i class="bi bi-calendar-check me-1"></i> Today's Orders</a>
    <a href="{{ route('reports.orders', ['period' => 'yesterday']) }}" class="btn btn-sm {{ request('period') === 'yesterday' ? 'btn-secondary' : 'btn-light border' }}">Yesterday</a>
    <a href="{{ route('reports.orders', ['period' => '7days']) }}" class="btn btn-sm {{ request('period') === '7days' ? 'btn-secondary' : 'btn-light border' }}">Last 7 Days</a>
    <a href="{{ route('reports.orders') }}" class="btn btn-sm {{ !request('period') && !request('date_from') ? 'btn-dark' : 'btn-light border' }}">All Records</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('reports.orders') }}" method="GET" class="row g-3">
            @if(request('period'))
                <input type="hidden" name="period" value="{{ request('period') }}">
            @endif
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Status Filter</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="NEW" {{ request('status') == 'NEW' ? 'selected' : '' }}>NEW</option>
                    <option value="VERIFIED" {{ request('status') == 'VERIFIED' ? 'selected' : '' }}>VERIFIED</option>
                    <option value="ACCEPTED" {{ request('status') == 'ACCEPTED' ? 'selected' : '' }}>ACCEPTED</option>
                    <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                    <option value="SHIPPED" {{ request('status') == 'SHIPPED' ? 'selected' : '' }}>SHIPPED</option>
                    <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-50"><i class="bi bi-filter"></i> Filter</button>
                <div class="dropdown w-50">
                    <button class="btn btn-success dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="{{ route('reports.orders.csv', request()->all()) }}"><i class="bi bi-filetype-csv text-success me-2"></i> Export CSV</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.orders.excel', request()->all()) }}"><i class="bi bi-file-earmark-excel text-success me-2"></i> Export Excel</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.orders.json', request()->all()) }}"><i class="bi bi-filetype-json text-primary me-2"></i> Export JSON</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.orders.txt', request()->all()) }}"><i class="bi bi-filetype-txt text-dark me-2"></i> Export TXT</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('reports.orders.print', request()->all()) }}" target="_blank"><i class="bi bi-printer text-danger me-2"></i> Print / Save PDF</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 fs-7">
            <thead class="table-light">
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Phone Number</th>
                    <th>Order Status</th>
                    <th>Label Status</th>
                    <th>Packing Status</th>
                    <th>Order Date</th>
                    <th>Label File</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="fw-bold text-primary"><a href="{{ route('orders.show', $order->id) }}">{{ $order->order_id }}</a></td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->phone_number }}</td>
                        <td><x-status-badge :status="$order->status" type="order" /></td>
                        <td><x-status-badge :status="$order->label_status" type="label" /></td>
                        <td><x-status-badge :status="$order->packing_status" type="packing" /></td>
                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $order->label ? $order->label->file_name : 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No report data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

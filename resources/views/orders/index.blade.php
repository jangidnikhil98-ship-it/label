@extends('layouts.app')

@section('title', 'Orders Management')
@section('page-title', 'Orders List')

@section('content')
<!-- Top Quick Action Bar: Today's Orders & Date Presets -->
<div class="row g-3 mb-4 align-items-center">
    <div class="col-md-7">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('orders.index', ['period' => 'today']) }}" class="btn {{ request('period') === 'today' ? 'btn-primary' : 'btn-outline-primary' }} fw-semibold shadow-sm">
                <i class="bi bi-calendar-check-fill me-1"></i> Today's Orders 
                <span class="badge bg-white text-primary ms-1 fw-bold">{{ $todayCount ?? 0 }}</span>
            </a>
            
            <span class="text-muted">|</span>
            
            <a href="{{ route('orders.index', ['period' => 'yesterday']) }}" class="btn btn-sm {{ request('period') === 'yesterday' ? 'btn-secondary' : 'btn-light border' }}">Yesterday</a>
            <a href="{{ route('orders.index', ['period' => '3days']) }}" class="btn btn-sm {{ request('period') === '3days' ? 'btn-secondary' : 'btn-light border' }} fw-semibold"><i class="bi bi-clock-history me-1"></i> Last 3 Days <span class="badge {{ request('period') === '3days' ? 'bg-white text-dark' : 'bg-primary text-white' }} ms-1">{{ $last3DaysCount ?? 0 }}</span></a>
            <a href="{{ route('orders.index', ['period' => '7days']) }}" class="btn btn-sm {{ request('period') === '7days' ? 'btn-secondary' : 'btn-light border' }}">Last 7 Days</a>
            <a href="{{ route('orders.index', ['period' => 'month']) }}" class="btn btn-sm {{ request('period') === 'month' ? 'btn-secondary' : 'btn-light border' }}">This Month</a>
            <a href="{{ route('orders.index') }}" class="btn btn-sm {{ !request('period') && !request('date') && !request('date_from') ? 'btn-dark' : 'btn-light border' }}">All Time</a>
        </div>
    </div>
    
    <div class="col-md-5 text-end">
        <!-- Multi-Format Export Dropdown -->
        <div class="btn-group shadow-sm">
            <button type="button" class="btn btn-success dropdown-toggle fw-semibold" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download me-1"></i> Export Orders
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header text-uppercase fs-8">Select File Format</h6></li>
                <li><a class="dropdown-item" href="{{ route('reports.orders.csv', request()->all()) }}"><i class="bi bi-filetype-csv text-success me-2 fs-6"></i> Export CSV (.csv)</a></li>
                <li><a class="dropdown-item" href="{{ route('reports.orders.excel', request()->all()) }}"><i class="bi bi-file-earmark-excel text-success me-2 fs-6"></i> Export Excel (.xlsx compatible)</a></li>
                <li><a class="dropdown-item" href="{{ route('reports.orders.json', request()->all()) }}"><i class="bi bi-filetype-json text-primary me-2 fs-6"></i> Export JSON (.json)</a></li>
                <li><a class="dropdown-item" href="{{ route('reports.orders.txt', request()->all()) }}"><i class="bi bi-filetype-txt text-dark me-2 fs-6"></i> Export TXT List (.txt)</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('reports.orders.print', request()->all()) }}" target="_blank"><i class="bi bi-printer text-danger me-2 fs-6"></i> Print / Save PDF Report</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Filter & Search Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('orders.index') }}" method="GET" class="row g-3">
            @if(request('period'))
                <input type="hidden" name="period" value="{{ request('period') }}">
            @endif
            @if(request('tab'))
                <input type="hidden" name="tab" value="{{ request('tab') }}">
            @endif

            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Order ID, Customer, Phone..." value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-md-2">
                <label class="form-label fs-7 fw-semibold">Order Status</label>
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

            <div class="col-md-2">
                <label class="form-label fs-7 fw-semibold">Label Status</label>
                <select name="label_status" class="form-select">
                    <option value="">All Label Statuses</option>
                    <option value="MATCHED" {{ request('label_status') == 'MATCHED' ? 'selected' : '' }}>Matched</option>
                    <option value="PENDING" {{ request('label_status') == 'PENDING' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Date From / To</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
            </div>

            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-list-task me-2"></i> Orders Overview ({{ $orders->total() }})</h6>
        
        <!-- Bulk Actions -->
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Bulk Actions
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="executeBulkAction('mark_verified')"><i class="bi bi-check-lg text-primary me-2"></i> Mark as Verified</a></li>
                <li><a class="dropdown-item" href="#" onclick="executeBulkAction('mark_accepted')"><i class="bi bi-check-circle-fill text-success me-2"></i> Mark as Accepted</a></li>
                <li><a class="dropdown-item" href="#" onclick="executeBulkAction('mark_packed')"><i class="bi bi-box-seam text-dark me-2"></i> Mark as Packed</a></li>
            </ul>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th width="40"><input type="checkbox" id="selectAll"></th>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Phone Number</th>
                    <th>WhatsApp Date</th>
                    <th>Order Status</th>
                    <th>Label Status</th>
                    <th>Packing Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td><input type="checkbox" class="order-checkbox" value="{{ $order->id }}"></td>
                        <td class="fw-bold"><a href="{{ route('orders.show', $order->id) }}" class="text-primary">{{ $order->order_id }}</a></td>
                        <td>{{ $order->customer_name }}</td>
                        <td><a href="tel:{{ $order->phone_number }}" class="text-decoration-none"><i class="bi bi-telephone text-muted me-1"></i>{{ $order->phone_number }}</a></td>
                        <td class="fs-7 text-muted">{{ $order->message ? $order->message->created_at->format('d M H:i') : '—' }}</td>
                        <td><x-status-badge :status="$order->status" type="order" /></td>
                        <td><x-status-badge :status="$order->label_status" type="label" /></td>
                        <td><x-status-badge :status="$order->packing_status" type="packing" /></td>
                        <td class="fs-7 text-muted">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                                @if($order->label)
                                    <a href="{{ route('labels.view', $order->label->id) }}" target="_blank" class="btn btn-outline-secondary" title="View Shipping Label"><i class="bi bi-file-earmark-pdf"></i> Label</a>
                                @else
                                    <form action="{{ route('meesho.process-order', $order->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning" title="Accept on Meesho & Get Label">
                                            <i class="bi bi-lightning-charge"></i> Meesho
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No orders found matching the selected criteria.</td>
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

@push('scripts')
<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    function executeBulkAction(action) {
        const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Please select at least one order.');
            return;
        }

        if (!confirm(`Are you sure you want to perform this bulk action on ${selected.length} orders?`)) {
            return;
        }

        fetch("{{ route('orders.bulk-action') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                order_ids: selected,
                action: action
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.message || 'Operation failed', 'danger');
            }
        });
    }
</script>
@endpush

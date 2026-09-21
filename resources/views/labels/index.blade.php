@extends('layouts.app')

@section('title', 'All Labels')
@section('page-title', 'Shipping Labels Overview')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('labels.index') }}" method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by Detected Order ID or File Name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="LABEL_MATCHED" {{ request('status') == 'LABEL_MATCHED' ? 'selected' : '' }}>Matched</option>
                    <option value="LABEL_UNMATCHED" {{ request('status') == 'LABEL_UNMATCHED' ? 'selected' : '' }}>Unmatched</option>
                    <option value="LABEL_PENDING" {{ request('status') == 'LABEL_PENDING' ? 'selected' : '' }}>Pending</option>
                    <option value="LABEL_ERROR" {{ request('status') == 'LABEL_ERROR' ? 'selected' : '' }}>Error</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-3 text-end">
                <a href="{{ route('labels.import') }}" class="btn btn-success"><i class="bi bi-upload"></i> Import Labels</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 fs-7">
            <thead class="table-light">
                <tr>
                    <th>File Name</th>
                    <th>Type</th>
                    <th>Detected Order ID</th>
                    <th>Matched Order</th>
                    <th>Confidence</th>
                    <th>Status</th>
                    <th>Processed At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($labels as $label)
                    <tr>
                        <td class="fw-bold">{{ $label->file_name }}</td>
                        <td><span class="badge bg-secondary">{{ strtoupper($label->file_type) }}</span></td>
                        <td class="fw-bold text-primary">{{ $label->detected_order_id ?: '—' }}</td>
                        <td>
                            @if($label->order)
                                <a href="{{ route('orders.show', $label->order->id) }}" class="badge bg-success text-decoration-none">{{ $label->order->order_id }}</a>
                            @else
                                <span class="text-muted">Unmatched</span>
                            @endif
                        </td>
                        <td>{{ $label->confidence }}%</td>
                        <td><x-status-badge :status="$label->status" type="label" /></td>
                        <td class="text-muted">{{ $label->processed_at ? $label->processed_at->format('d M Y H:i') : '—' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('labels.view', $label->id) }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                                <a href="{{ route('labels.download', $label->id) }}" class="btn btn-outline-secondary"><i class="bi bi-download"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No labels found. <a href="{{ route('labels.import') }}">Import labels</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $labels->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

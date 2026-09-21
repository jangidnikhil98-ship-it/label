@extends('layouts.app')

@section('title', 'Label Reports')
@section('page-title', 'Shipping Label Reports')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('reports.labels') }}" method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Label Statuses</option>
                    <option value="LABEL_MATCHED" {{ request('status') == 'LABEL_MATCHED' ? 'selected' : '' }}>Matched</option>
                    <option value="LABEL_UNMATCHED" {{ request('status') == 'LABEL_UNMATCHED' ? 'selected' : '' }}>Unmatched</option>
                    <option value="LABEL_PENDING" {{ request('status') == 'LABEL_PENDING' ? 'selected' : '' }}>Pending</option>
                    <option value="LABEL_ERROR" {{ request('status') == 'LABEL_ERROR' ? 'selected' : '' }}>Error</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Apply Filter</button>
                <a href="{{ route('reports.labels.csv', request()->all()) }}" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 fs-7">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>File Name</th>
                    <th>Type</th>
                    <th>Detected Order ID</th>
                    <th>Matched Order</th>
                    <th>Confidence</th>
                    <th>Status</th>
                    <th>Processed At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($labels as $label)
                    <tr>
                        <td>{{ $label->id }}</td>
                        <td class="fw-bold">{{ $label->file_name }}</td>
                        <td><span class="badge bg-secondary">{{ strtoupper($label->file_type) }}</span></td>
                        <td class="fw-bold text-primary">{{ $label->detected_order_id ?: 'N/A' }}</td>
                        <td>{{ $label->order ? $label->order->order_id : 'N/A' }}</td>
                        <td>{{ $label->confidence }}%</td>
                        <td><x-status-badge :status="$label->status" type="label" /></td>
                        <td class="text-muted">{{ $label->processed_at ? $label->processed_at->format('Y-m-d H:i') : 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No label report data found.</td>
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

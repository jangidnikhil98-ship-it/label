@extends('layouts.app')

@section('title', 'Pending / Unmatched Labels')
@section('page-title', 'Unmatched Shipping Labels')

@section('content')
<div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
    <div>
        <strong>Manual Verification Required</strong><br>
        These shipping labels could not be automatically matched to an existing WhatsApp order.
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 fs-7">
            <thead class="table-light">
                <tr>
                    <th>File Name</th>
                    <th>Detected Order ID</th>
                    <th>Confidence</th>
                    <th>Uploaded At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($labels as $label)
                    <tr>
                        <td class="fw-bold">{{ $label->file_name }}</td>
                        <td class="fw-bold text-danger">{{ $label->detected_order_id ?: 'No ID Found' }}</td>
                        <td>{{ $label->confidence }}%</td>
                        <td class="text-muted">{{ $label->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <a href="{{ route('labels.view', $label->id) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View Label File</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">🎉 Great job! No unmatched labels pending.</td>
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

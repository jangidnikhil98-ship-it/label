@extends('layouts.app')

@section('title', 'WhatsApp Messages')
@section('page-title', 'WhatsApp Messages Feed')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0 text-dark"><i class="bi bi-chat-text me-2"></i> Messages Feed</h6>
        <a href="{{ route('whatsapp.import') }}" class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i> Import Message</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 fs-7">
            <thead class="table-light">
                <tr>
                    <th>Customer</th>
                    <th>Message Type</th>
                    <th>Content Snippet</th>
                    <th>Linked Order</th>
                    <th>Received At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td class="fw-bold">{{ $message->chat ? $message->chat->customer_name : 'Guest' }} ({{ $message->chat ? $message->chat->phone_number : '—' }})</td>
                        <td><span class="badge bg-secondary">{{ strtoupper($message->message_type) }}</span></td>
                        <td class="text-wrap" style="max-width: 300px;">{{ Str::limit($message->message_text, 100) }}</td>
                        <td>
                            @if($message->order)
                                <a href="{{ route('orders.show', $message->order->id) }}" class="badge bg-primary text-decoration-none">{{ $message->order->order_id }}</a>
                            @else
                                <span class="text-muted">Unlinked</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $message->created_at->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No messages found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $messages->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

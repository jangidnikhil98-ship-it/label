@extends('layouts.app')

@section('title', 'WhatsApp Chats')
@section('page-title', 'WhatsApp Customer Chats')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold m-0 text-dark"><i class="bi bi-chat-dots me-2"></i> WhatsApp Conversations</h6>
        <a href="{{ route('whatsapp.import') }}" class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i> Import New Message</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 fs-7">
            <thead class="table-light">
                <tr>
                    <th>Customer Name</th>
                    <th>Phone Number</th>
                    <th>Total Messages</th>
                    <th>Linked Orders</th>
                    <th>Last Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($chats as $chat)
                    <tr>
                        <td class="fw-bold">{{ $chat->customer_name }}</td>
                        <td>{{ $chat->phone_number }}</td>
                        <td><span class="badge bg-secondary">{{ $chat->messages_count }}</span></td>
                        <td><span class="badge bg-info text-dark">{{ $chat->orders->count() }}</span></td>
                        <td class="text-muted">{{ $chat->updated_at->format('d M Y H:i') }}</td>
                        <td>
                            <a href="{{ route('whatsapp.messages', ['chat_id' => $chat->id]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View Chat</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No WhatsApp chats found. <a href="{{ route('whatsapp.import') }}">Import customer messages</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $chats->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

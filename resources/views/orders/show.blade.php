@extends('layouts.app')

@section('title', 'Order Details - ' . $order->order_id)
@section('page-title', 'Order ' . $order->order_id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Back to Orders</a>
        <h4 class="fw-bold mb-0">Order ID: {{ $order->order_id }}</h4>
    </div>
    <div class="d-flex gap-2">
        @if($order->status === 'NEW')
            <button onclick="updateOrderStatus('VERIFIED')" class="btn btn-primary"><i class="bi bi-shield-check"></i> Mark as Verified</button>
        @endif

        @if(in_array($order->status, ['NEW', 'VERIFIED']))
            <button onclick="updateOrderStatus('ACCEPTED')" class="btn btn-success"><i class="bi bi-check-circle"></i> Accept Order</button>
            <button onclick="updateOrderStatus('REJECTED')" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Reject Order</button>
        @endif

        @if($order->label && $order->label->status === 'LABEL_MATCHED')
            <a href="{{ route('packing.index', ['order_id' => $order->id]) }}" class="btn btn-dark"><i class="bi bi-box-seam"></i> Open in Packing Mode</a>
        @endif
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Info & Message -->
    <div class="col-lg-7">
        <!-- Customer & Order Overview Cards -->
        <div class="row g-3 mb-4">
            <!-- Customer Info -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold m-0 text-dark"><i class="bi bi-person me-2"></i> Customer Information</h6>
                    </div>
                    <div class="card-body fs-7">
                        <div class="mb-2"><strong>Name:</strong> {{ $order->customer_name }}</div>
                        <div class="mb-2"><strong>Phone:</strong> <a href="tel:{{ $order->phone_number }}">{{ $order->phone_number }}</a></div>
                        <div><strong>WhatsApp Chat:</strong> 
                            @if($order->chat)
                                <a href="{{ route('whatsapp.messages', ['chat_id' => $order->chat->id]) }}" class="badge bg-success-subtle text-success border border-success text-decoration-none">
                                    <i class="bi bi-whatsapp"></i> View Chat History
                                </a>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Info -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold m-0 text-dark"><i class="bi bi-info-circle me-2"></i> Order Information</h6>
                    </div>
                    <div class="card-body fs-7">
                        <div class="mb-2"><strong>Order ID:</strong> <span class="badge bg-light text-dark border">{{ $order->order_id }}</span></div>
                        <div class="mb-2"><strong>Status:</strong> <x-status-badge :status="$order->status" type="order" /></div>
                        <div class="mb-2"><strong>Packing Status:</strong> <x-status-badge :status="$order->packing_status" type="packing" /></div>
                        <div><strong>Source:</strong> <span class="badge bg-secondary">{{ $order->source }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Original WhatsApp Message -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-chat-left-text me-2"></i> Original WhatsApp Message</h6>
            </div>
            <div class="card-body">
                @if($order->message)
                    <div class="p-3 bg-light rounded border fs-7 mb-3">
                        {!! nl2br(e($order->message->message_text)) !!}
                    </div>

                    @if($order->message->media_path)
                        <div class="mb-3">
                            <label class="form-label fs-7 fw-semibold">Attached Image Preview:</label>
                            <div>
                                <img src="{{ asset('storage/' . $order->message->media_path) }}" alt="WhatsApp Attachment" class="img-fluid rounded border max-vh-40" style="max-height: 250px;">
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-muted fs-7 mb-0">No original message linked.</p>
                @endif
            </div>
        </div>

        <!-- Order Timeline -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-clock-history me-2"></i> Order Timeline</h6>
            </div>
            <div class="card-body fs-7">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-check-circle-fill text-success me-2"></i> Order Received</span>
                        <span class="text-muted">{{ $order->created_at->format('d M Y H:i:s') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-check-circle-fill text-success me-2"></i> Order ID Extracted (Confidence: {{ $order->extraction_confidence }}%)</span>
                        <span class="text-muted">{{ $order->created_at->format('d M Y H:i:s') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi {{ $order->verified_at ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }} me-2"></i> Order Verified</span>
                        <span class="text-muted">{{ $order->verified_at ? $order->verified_at->format('d M Y H:i:s') : 'Pending' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi {{ $order->accepted_at ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }} me-2"></i> Order Accepted</span>
                        <span class="text-muted">{{ $order->accepted_at ? $order->accepted_at->format('d M Y H:i:s') : 'Pending' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi {{ $order->label ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }} me-2"></i> Shipping Label Imported & Matched</span>
                        <span class="text-muted">{{ $order->label ? $order->label->created_at->format('d M Y H:i:s') : 'Pending' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi {{ $order->packed_at ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }} me-2"></i> Packed & Ready for Dispatch</span>
                        <span class="text-muted">{{ $order->packed_at ? $order->packed_at->format('d M Y H:i:s') : 'Pending' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Shipping Label Card & Actions -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-qr-code-scan me-2"></i> Meesho Shipping Label</h6>
                <x-status-badge :status="$order->label_status" type="label" />
            </div>
            <div class="card-body">
                @if($order->label)
                    <div class="mb-3 fs-7">
                        <div><strong>File Name:</strong> {{ $order->label->file_name }}</div>
                        <div><strong>Detected ID:</strong> {{ $order->label->detected_order_id }}</div>
                        <div><strong>Matching Confidence:</strong> {{ $order->label->confidence }}%</div>
                    </div>

                    <!-- Actions -->
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('labels.view', $order->label->id) }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-eye"></i> View Label</a>
                        <a href="{{ route('labels.download', $order->label->id) }}" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Download Label</a>
                        <button onclick="printLabelIframe('{{ route('labels.view', $order->label->id) }}')" class="btn btn-primary"><i class="bi bi-printer"></i> Print Label</button>
                    </div>

                    <!-- Label Preview Frame -->
                    @if(in_array($order->label->file_type, ['jpg', 'jpeg', 'png']))
                        <div class="border rounded p-2 text-center bg-light">
                            <img src="{{ route('labels.view', $order->label->id) }}" alt="Shipping Label" class="img-fluid" style="max-height: 400px;">
                        </div>
                    @else
                        <div class="ratio ratio-4x3 border rounded">
                            <iframe src="{{ route('labels.view', $order->label->id) }}" title="Label Preview"></iframe>
                        </div>
                    @endif
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-file-earmark-x display-4"></i>
                        <p class="mt-2 mb-3 fs-7">No shipping label attached yet.</p>
                        <a href="{{ route('labels.import') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload"></i> Upload Shipping Label</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateOrderStatus(newStatus) {
        if (!confirm(`Are you sure you want to change order status to ${newStatus}?`)) return;

        fetch("{{ route('orders.update-status', $order->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    function printLabelIframe(url) {
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = url;
        document.body.appendChild(iframe);
        iframe.onload = function() {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        };
    }
</script>
@endpush

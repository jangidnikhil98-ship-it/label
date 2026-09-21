@extends('layouts.app')

@section('title', 'Packing Mode')
@section('page-title', 'Packing Mode')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <!-- Top Stats Bar -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 bg-primary text-white d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <div class="text-white-50 fs-7 text-uppercase fw-semibold">Orders Pending Packing</div>
                        <div id="remainingBadge" class="display-6 fw-bold">{{ $remainingCount }}</div>
                    </div>
                    <i class="bi bi-box-seam display-4"></i>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 bg-dark text-white d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <div class="text-white-50 fs-7 text-uppercase fw-semibold">Packed Today</div>
                        <div id="packedTodayBadge" class="display-6 fw-bold">{{ $packedTodayCount }}</div>
                    </div>
                    <i class="bi bi-check-circle-fill text-success display-4"></i>
                </div>
            </div>
        </div>

        @if($currentOrder)
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold"><i class="bi bi-box-seam text-warning me-2"></i> PACKING MODE</h5>
                    <span class="badge bg-success fs-7"><i class="bi bi-check2-all me-1"></i> Label Ready</span>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4">
                        <!-- Left Details Box -->
                        <div class="col-md-5 d-flex flex-column justify-content-between border-end">
                            <div>
                                <div class="mb-3">
                                    <span class="text-muted fs-7 text-uppercase fw-bold">Order ID</span>
                                    <h2 id="orderIdText" class="fw-extrabold text-primary m-0">{{ $currentOrder->order_id }}</h2>
                                </div>

                                <div class="mb-3">
                                    <span class="text-muted fs-7 text-uppercase fw-bold">Customer Name</span>
                                    <h4 id="customerNameText" class="fw-bold text-dark m-0">{{ $currentOrder->customer_name }}</h4>
                                </div>

                                <div class="mb-3">
                                    <span class="text-muted fs-7 text-uppercase fw-bold">Phone Number</span>
                                    <h4 id="phoneNumberText" class="fw-bold text-dark m-0">{{ $currentOrder->phone_number }}</h4>
                                </div>

                                <div class="mb-3">
                                    <span class="text-muted fs-7 text-uppercase fw-bold">Label Status</span>
                                    <div><span id="labelStatusBadge" class="badge bg-success fs-6">✓ READY</span></div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button onclick="printCurrentLabel()" class="btn btn-outline-primary btn-lg py-3 fw-bold">
                                    <i class="bi bi-printer-fill me-2"></i> Print Label
                                </button>
                                
                                <button id="packNextBtn" onclick="markPackedAndNext({{ $currentOrder->id }})" class="btn btn-success btn-lg py-3 fs-5 fw-bold shadow">
                                    <i class="bi bi-arrow-right-circle-fill me-2"></i> Mark as Packed & Next
                                </button>
                            </div>
                        </div>

                        <!-- Right Label Preview Frame -->
                        <div class="col-md-7">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-header bg-white py-2 fw-semibold text-center border-bottom">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Shipping Label Preview
                                </div>
                                <div class="card-body p-2 d-flex justify-content-center align-items-center">
                                    @if($currentOrder->label)
                                        @if(in_array($currentOrder->label->file_type, ['jpg', 'jpeg', 'png']))
                                            <img id="labelImgPreview" src="{{ route('labels.view', $currentOrder->label->id) }}" alt="Shipping Label" class="img-fluid rounded shadow-sm" style="max-height: 450px;">
                                        @else
                                            <iframe id="labelPdfFrame" src="{{ route('labels.view', $currentOrder->label->id) }}" class="w-100 rounded border shadow-sm" style="height: 450px;"></iframe>
                                        @endif
                                    @else
                                        <div class="text-muted text-center py-5">No label file attached.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- All Packed View -->
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i class="bi bi-emoji-smile text-success display-1 mb-3"></i>
                    <h3 class="fw-bold">All Ready Orders Packed!</h3>
                    <p class="text-muted fs-6">There are no remaining matched orders waiting for packing at this moment.</p>
                    <a href="{{ route('orders.index') }}" class="btn btn-primary px-4"><i class="bi bi-cart me-1"></i> Go to Orders</a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentLabelUrl = "{{ $currentOrder && $currentOrder->label ? route('labels.view', $currentOrder->label->id) : '' }}";

    function printCurrentLabel() {
        if (!currentLabelUrl) {
            alert('No label file available to print.');
            return;
        }
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = currentLabelUrl;
        document.body.appendChild(iframe);
        iframe.onload = function() {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        };
    }

    function markPackedAndNext(orderId) {
        const btn = document.getElementById('packNextBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Updating...';

        fetch(`/packing/${orderId}/pack`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');

                if (data.has_next && data.next_order) {
                    // Instantly update UI fields without full reload
                    const next = data.next_order;
                    document.getElementById('orderIdText').innerText = next.order_id;
                    document.getElementById('customerNameText').innerText = next.customer_name;
                    document.getElementById('phoneNumberText').innerText = next.phone_number;
                    document.getElementById('remainingBadge').innerText = data.remaining_count;
                    currentLabelUrl = next.label_url;

                    const packBtn = document.getElementById('packNextBtn');
                    packBtn.setAttribute('onclick', `markPackedAndNext(${next.id})`);
                    packBtn.disabled = false;
                    packBtn.innerHTML = '<i class="bi bi-arrow-right-circle-fill me-2"></i> Mark as Packed & Next';

                    // Update label frame
                    const iframe = document.getElementById('labelPdfFrame');
                    const img = document.getElementById('labelImgPreview');
                    if (iframe) iframe.src = next.label_url;
                    if (img) img.src = next.label_url;
                } else {
                    location.reload();
                }
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-right-circle-fill me-2"></i> Mark as Packed & Next';
                showToast(data.message || 'Operation failed', 'danger');
            }
        });
    }
</script>
@endpush

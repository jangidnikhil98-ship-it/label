@extends('layouts.app')

@section('title', 'WhatsApp Web Gateway & Chat Import')
@section('page-title', 'WhatsApp Web Gateway & Chat Import')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">

        <!-- Automated WhatsApp Web Gateway Card -->
        <div class="card border-0 shadow-sm mb-4 bg-light">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0"><i class="bi bi-qr-code-scan me-2"></i> Automated WhatsApp Web Gateway (Live QR Code Connection)</h6>
                <span id="gatewayStatusBadge" class="badge bg-warning text-dark fs-7"><i class="bi bi-arrow-repeat spinner-border spinner-border-sm me-1"></i> Checking Gateway...</span>
            </div>
            <div class="card-body bg-white">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center border-end py-2">
                        <div id="qrContainer" class="p-2 border rounded bg-light d-inline-block shadow-sm style-qr">
                            <div class="spinner-border text-success my-4" role="status"></div>
                            <div class="text-muted fs-7">Loading WhatsApp QR...</div>
                        </div>
                        <div class="mt-2 text-muted fs-7">Scan with WhatsApp on phone (<span class="fw-semibold">Linked Devices</span>)</div>
                    </div>
                    <div class="col-md-8 ps-md-4">
                        <h6 class="fw-bold text-dark mb-2">Automated Real-Time Order Extraction</h6>
                        <p class="text-muted fs-7 mb-3">
                            Connect your WhatsApp account to automatically capture incoming customer messages and extract Order IDs in real time—no manual exports or file uploads required!
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <button id="sync3DaysBtn" class="btn btn-sm btn-success fw-semibold"><i class="bi bi-clock-history me-1"></i> Sync Last 3 Days Orders</button>
                            <button id="syncTodayBtn" class="btn btn-sm btn-outline-success fw-semibold"><i class="bi bi-arrow-repeat me-1"></i> Sync Today Only</button>
                            <a href="{{ route('orders.index', ['period' => '3days']) }}" class="btn btn-sm btn-primary fw-semibold"><i class="bi bi-calendar-check me-1"></i> View Last 3 Days Orders</a>
                            <button id="disconnectGatewayBtn" class="btn btn-sm btn-outline-danger d-none"><i class="bi bi-power me-1"></i> Disconnect Gateway</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Upload Fallbacks -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-upload text-primary me-2"></i> Manual Upload & Text Parsing Fallback</h6>
            </div>
            <div class="card-body">
                <form id="importMessageForm" action="{{ route('whatsapp.import.process') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-primary"><i class="bi bi-file-earmark-arrow-up me-1"></i> Upload WhatsApp Chat Export (.zip archive, .txt file, .pdf document, or Screenshot)</label>
                        <input type="file" name="message_file" class="form-control" accept=".zip,.txt,.pdf,image/*">
                        <div class="form-text">Supports ZIP with media, standalone TXT chat exports, PDF invoices, and chat screenshot images.</div>
                    </div>

                    <div class="text-center text-muted fw-bold my-3">- OR -</div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Paste Raw WhatsApp Message Text</label>
                        <textarea name="raw_text" class="form-control" rows="4" placeholder="Paste customer WhatsApp message here...
Example:
Hello sir
My order id is OD4089238472
Mobile: 9876543210"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer Name (Optional)</label>
                        <input type="text" name="customer_name" class="form-control" placeholder="e.g. Rahul, Amit...">
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" id="submitBtn" class="btn btn-success px-4 py-2 fw-semibold">
                            <i class="bi bi-magic me-1"></i> Extract & Import Order Information
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Single Order Result Card -->
        <div id="resultCard" class="card border-0 shadow-sm d-none mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-check-circle-fill text-success me-2"></i> Single Order Extracted</h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i> Export Order
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('reports.orders.csv', ['period' => 'today']) }}"><i class="bi bi-filetype-csv me-2"></i> CSV Format</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.orders.excel', ['period' => 'today']) }}"><i class="bi bi-file-earmark-excel me-2"></i> Excel Format</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.orders.json', ['period' => 'today']) }}"><i class="bi bi-filetype-json me-2"></i> JSON Format</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.orders.txt', ['period' => 'today']) }}"><i class="bi bi-filetype-txt me-2"></i> TXT List</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="mb-2"><strong>Extracted Order ID:</strong> <span id="resOrderId" class="badge bg-primary fs-6"></span></div>
                        <div class="mb-2"><strong>Phone Number:</strong> <span id="resPhone" class="fw-bold"></span></div>
                        <div><strong>Customer Name:</strong> <span id="resCustomer"></span></div>
                    </div>
                    <div class="col-md-4 text-end">
                        <a id="resViewOrderBtn" href="#" class="btn btn-outline-primary"><i class="bi bi-arrow-right-circle"></i> View Created Order</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Export Extraction Summary & List -->
        <div id="zipResultCard" class="card border-0 shadow-sm d-none mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-file-earmark-zip text-primary me-2"></i> Chat Export Processing Results</h6>
                <div>
                    <div class="btn-group me-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> Export Results
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('reports.orders.csv') }}"><i class="bi bi-filetype-csv me-2"></i> Export CSV</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.orders.excel') }}"><i class="bi bi-file-earmark-excel me-2"></i> Export Excel</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.orders.json') }}"><i class="bi bi-filetype-json me-2"></i> Export JSON</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.orders.txt') }}"><i class="bi bi-filetype-txt me-2"></i> Export TXT</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.orders.print') }}" target="_blank"><i class="bi bi-printer me-2"></i> Print / Save PDF</a></li>
                        </ul>
                    </div>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-primary"><i class="bi bi-cart me-1"></i> View All Orders</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4 text-center">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border">
                            <div class="text-muted fs-7">Messages Parsed</div>
                            <div id="zipMessagesParsed" class="fw-bold fs-4 text-dark">0</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border">
                            <div class="text-muted fs-7">Orders Created</div>
                            <div id="zipOrdersCreated" class="fw-bold fs-4 text-success">0</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border">
                            <div class="text-muted fs-7">Duplicates Skipped</div>
                            <div id="zipDuplicates" class="fw-bold fs-4 text-warning">0</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Extracted Orders List:</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-7">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer Name</th>
                                <th>Phone Number</th>
                                <th>Attached Media</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="zipOrdersTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Duplicate Modal -->
<div class="modal fade" id="duplicateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Duplicate Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body fs-7">
                <p class="fw-bold mb-2">This Order ID already exists in the system!</p>
                <div class="p-3 bg-light rounded border">
                    <div><strong>Order ID:</strong> <span id="dupOrderId"></span></div>
                    <div><strong>Customer Name:</strong> <span id="dupCustomer"></span></div>
                    <div><strong>Phone:</strong> <span id="dupPhone"></span></div>
                    <div><strong>Current Status:</strong> <span id="dupStatus"></span></div>
                    <div><strong>Created At:</strong> <span id="dupDate"></span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a id="dupViewBtn" href="#" class="btn btn-primary"><i class="bi bi-eye"></i> View Existing Order</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Poll WhatsApp Gateway Status & QR code
    function checkGatewayStatus() {
        fetch('/api/v1/whatsapp/gateway/status')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('gatewayStatusBadge');
                const qrBox = document.getElementById('qrContainer');
                const discBtn = document.getElementById('disconnectGatewayBtn');

                if (data.status === 'CONNECTED') {
                    badge.className = 'badge bg-success fs-7';
                    badge.innerHTML = `<i class="bi bi-check-circle me-1"></i> Connected: ${data.user || 'Active'}`;
                    qrBox.innerHTML = `
                        <div class="text-success p-3">
                            <i class="bi bi-check-circle-fill display-4"></i>
                            <div class="fw-bold mt-2">WhatsApp Connected</div>
                            <div class="fs-8 text-muted">${data.user || ''}</div>
                        </div>`;
                    discBtn.classList.remove('d-none');
                } else if (data.status === 'SCAN_QR' && data.qr) {
                    badge.className = 'badge bg-warning text-dark fs-7';
                    badge.innerHTML = `<i class="bi bi-qr-code me-1"></i> Scan QR Code`;
                    qrBox.innerHTML = `<img src="${data.qr}" alt="WhatsApp QR Code" class="img-fluid" style="max-width: 180px;">`;
                    discBtn.classList.add('d-none');
                } else {
                    badge.className = 'badge bg-secondary fs-7';
                    badge.innerHTML = `<i class="bi bi-power me-1"></i> Offline / Starting...`;
                    qrBox.innerHTML = `
                        <div class="p-3 text-muted">
                            <i class="bi bi-dash-circle display-4 text-secondary"></i>
                            <div class="mt-2">Gateway Offline</div>
                        </div>`;
                    discBtn.classList.add('d-none');
                }
            })
            .catch(err => {
                const badge = document.getElementById('gatewayStatusBadge');
                badge.className = 'badge bg-secondary fs-7';
                badge.innerText = 'Gateway Offline';
            });
    }

    checkGatewayStatus();
    setInterval(checkGatewayStatus, 5000);

    function performGatewaySync(days, btnElement, defaultText) {
        btnElement.disabled = true;
        btnElement.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Syncing Last ${days} Days Messages...`;
        fetch('/api/v1/whatsapp/gateway/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ days: days })
        })
        .then(res => res.json())
        .then(data => {
            btnElement.disabled = false;
            btnElement.innerHTML = defaultText;
            if (data.success) {
                showToast(data.message || 'Sync completed!', 'success');
                setTimeout(() => {
                    window.location.href = "{{ route('orders.index') }}?period=" + (days === 1 ? 'today' : '3days');
                }, 1200);
            } else {
                showToast(data.message || 'Sync failed.', 'danger');
            }
        })
        .catch(() => {
            btnElement.disabled = false;
            btnElement.innerHTML = defaultText;
            showToast('Error communicating with WhatsApp Gateway.', 'danger');
        });
    }

    document.getElementById('sync3DaysBtn').addEventListener('click', function() {
        performGatewaySync(3, this, '<i class="bi bi-clock-history me-1"></i> Sync Last 3 Days Orders');
    });

    document.getElementById('syncTodayBtn').addEventListener('click', function() {
        performGatewaySync(1, this, '<i class="bi bi-arrow-repeat me-1"></i> Sync Today Only');
    });

    document.getElementById('disconnectGatewayBtn').addEventListener('click', function() {
        if (!confirm('Are you sure you want to disconnect your WhatsApp Web Gateway session?')) return;
        fetch('/api/v1/whatsapp/gateway/disconnect', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(res => res.json())
            .then(data => {
                showToast(data.message || 'Disconnected', 'info');
                checkGatewayStatus();
            });
    });

    document.getElementById('importMessageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Extracting & Importing...';

        document.getElementById('resultCard').classList.add('d-none');
        document.getElementById('zipResultCard').classList.add('d-none');

        fetch("{{ route('whatsapp.import.process') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-magic me-1"></i> Extract & Import Order Information';

            if (data.is_zip && data.success) {
                showToast(data.message, 'success');
                const s = data.summary;
                document.getElementById('zipResultCard').classList.remove('d-none');
                document.getElementById('zipMessagesParsed').innerText = s.processed_messages;
                document.getElementById('zipOrdersCreated').innerText = s.orders_created;
                document.getElementById('zipDuplicates').innerText = s.duplicates_count;

                const tbody = document.getElementById('zipOrdersTableBody');
                tbody.innerHTML = '';

                s.orders.forEach(item => {
                    const ord = item.order;
                    const isDup = item.is_duplicate;
                    const tr = document.createElement('tr');
                    
                    let mediaHtml = '<span class="text-muted">No Media</span>';
                    if (ord.message && ord.message.media_path) {
                        mediaHtml = `<a href="/storage/${ord.message.media_path}" target="_blank" class="btn btn-sm btn-light border"><i class="bi bi-image text-primary"></i> View Media</a>`;
                    }

                    tr.innerHTML = `
                        <td class="fw-bold text-primary">${ord.order_id}</td>
                        <td>${ord.customer_name}</td>
                        <td>${ord.phone_number}</td>
                        <td>${mediaHtml}</td>
                        <td><span class="badge ${isDup ? 'bg-warning text-dark' : 'bg-success'}">${isDup ? 'Duplicate Skipped' : 'Created'}</span></td>
                        <td>
                            <a href="/orders/${ord.id}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
                return;
            }

            if (!data.success && data.is_duplicate) {
                const dup = data.existing_order;
                document.getElementById('dupOrderId').innerText = dup.order_id;
                document.getElementById('dupCustomer').innerText = dup.customer_name;
                document.getElementById('dupPhone').innerText = dup.phone_number;
                document.getElementById('dupStatus').innerText = dup.status;
                document.getElementById('dupDate').innerText = dup.created_at;
                document.getElementById('dupViewBtn').href = `/orders/${dup.id}`;

                const modal = new bootstrap.Modal(document.getElementById('duplicateModal'));
                modal.show();
                return;
            }

            if (data.success) {
                showToast(data.message, 'success');
                document.getElementById('resultCard').classList.remove('d-none');
                document.getElementById('resOrderId').innerText = data.order.order_id;
                document.getElementById('resPhone').innerText = data.order.phone_number;
                document.getElementById('resCustomer').innerText = data.order.customer_name;
                document.getElementById('resViewOrderBtn').href = data.redirect;
            } else {
                showToast(data.message || 'Extraction failed.', 'danger');
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-magic me-1"></i> Extract & Import Order Information';
            showToast('Server error during message processing.', 'danger');
        });
    });
</script>
@endpush

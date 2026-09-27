@extends('layouts.app')

@section('title', 'Meesho RPA Automation & Shipping Labels')
@section('page-title', 'Meesho Order Automation (RPA Bot)')

@section('content')
<div class="container-fluid">
    <!-- Header Notification / Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Top Row: Bot Status & Session Connection -->
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold d-flex align-items-center">
                        <i class="bi bi-robot text-primary me-2 fs-5"></i> Meesho RPA Bot Status
                    </h6>
                    @if(!empty($botStatus['connected']))
                        <span class="badge bg-success px-3 py-2"><i class="bi bi-check2-circle me-1"></i> Connected & Active</span>
                    @else
                        <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-exclamation-circle me-1"></i> Not Connected</span>
                    @endif
                </div>
                <div class="card-body">
                    @if(!empty($botStatus['connected']))
                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted fs-7">Supplier Account:</span>
                                <strong class="text-dark">{{ $botStatus['accountName'] ?? 'Meesho Seller' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted fs-7">Auth Method:</span>
                                <span class="badge bg-secondary">{{ $botStatus['authMethod'] ?? 'Session' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted fs-7">Session Updated:</span>
                                <span class="text-muted fs-7">{{ !empty($botStatus['lastUpdated']) ? \Carbon\Carbon::parse($botStatus['lastUpdated'])->diffForHumans() : 'Just now' }}</span>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <form action="{{ route('meesho.connect') }}" method="POST">
                                @csrf
                                <input type="hidden" name="launch_browser" value="1">
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Open Meesho Panel
                                </button>
                            </form>
                            <form action="{{ route('meesho.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Clear current Meesho session?')">
                                    <i class="bi bi-power me-1"></i> Disconnect Session
                                </button>
                            </form>
                        </div>
                    @else
                        <p class="text-muted fs-7 mb-3">
                            Connect your Meesho Supplier account to enable automated order acceptance and 1-click AWB shipping label downloads.
                        </p>

                        <!-- Method 1: Launch Chrome Login Window -->
                        <form action="{{ route('meesho.connect') }}" method="POST" class="mb-3">
                            @csrf
                            <input type="hidden" name="launch_browser" value="1">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-browser-chrome me-2"></i> Launch Chrome to Login (With OTP)
                            </button>
                            <small class="text-muted d-block mt-1 fs-8">
                                Opens Google Chrome so you can enter mobile & OTP. Your session will be automatically saved.
                            </small>
                        </form>

                        <div class="d-flex align-items-center my-3">
                            <hr class="flex-grow-1 my-0">
                            <span class="px-2 text-muted fs-8 text-uppercase">or paste session cookies</span>
                            <hr class="flex-grow-1 my-0">
                        </div>

                        <!-- Method 2: Paste Cookies / Token -->
                        <form action="{{ route('meesho.connect') }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <textarea name="cookies_or_token" class="form-control font-monospace fs-8" rows="2" placeholder="Paste session cookies (JSON) or auth token..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-key me-1"></i> Save Session Cookies
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Side: Workflow Explanation & Features -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold d-flex align-items-center">
                        <i class="bi bi-diagram-3 text-success me-2 fs-5"></i> Automated WhatsApp &rarr; Meesho Pipeline
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="p-3 border rounded h-100 bg-light">
                                <div class="badge bg-success-subtle text-success border border-success-subtle mb-2">Step 1</div>
                                <h6 class="fw-bold fs-7 mb-1">WhatsApp Order</h6>
                                <p class="text-muted fs-8 mb-0">Incoming customer/reseller chat message is parsed for Meesho Order ID (e.g. <code>338440392393_1</code>).</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 border rounded h-100 bg-light">
                                <div class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2">Step 2</div>
                                <h6 class="fw-bold fs-7 mb-1">Meesho Accept (RPA)</h6>
                                <p class="text-muted fs-8 mb-0">RPA Bot searches the order on Meesho, marks it <strong>Ready to Ship</strong>, and fetches the courier AWB.</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 border rounded h-100 bg-light">
                                <div class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mb-2">Step 3</div>
                                <h6 class="fw-bold fs-7 mb-1">Print & Pack</h6>
                                <p class="text-muted fs-8 mb-0">Shipping label PDF (4x6 thermal or A4) is instantly ready to print in <strong>Packing Mode</strong>.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-body-tertiary rounded border d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fw-semibold fs-7 d-block">Automatic WhatsApp Processing</span>
                            <small class="text-muted fs-8">Auto-accept Meesho orders immediately when detected from WhatsApp</small>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">ENABLED</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Section: Detected Meesho Orders -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 fw-bold d-flex align-items-center">
                <i class="bi bi-cart4 text-dark me-2"></i> Meesho Orders Extracted from WhatsApp ({{ $meeshoOrders->count() }})
            </h6>
            <a href="{{ route('packing.index') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-box-seam me-1"></i> Open Packing Mode
            </a>
        </div>
        <div class="card-body p-0">
            @if($meeshoOrders->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted display-4"></i>
                    <h6 class="mt-3 text-muted">No Meesho orders detected yet.</h6>
                    <p class="text-muted fs-7">When a WhatsApp chat contains a Meesho Order ID (e.g. <code>338440392393_1</code>), it will appear here automatically.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-7">
                            <tr>
                                <th>Order / Sub-Order ID</th>
                                <th>Customer & Phone</th>
                                <th>WhatsApp Chat</th>
                                <th>Order Status</th>
                                <th>Shipping Label</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="fs-7">
                            @foreach($meeshoOrders as $order)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $order->order_id }}</div>
                                        <small class="text-muted">{{ $order->created_at->format('d M Y, h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->customer_name }}</div>
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $order->phone_number }}</small>
                                    </td>
                                    <td>
                                        @if($order->chat)
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-whatsapp text-success me-1"></i> {{ $order->chat->customer_name ?? $order->chat->phone_number }}
                                            </span>
                                        @else
                                            <span class="text-muted fs-8">Direct</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->status === 'ACCEPTED')
                                            <span class="badge bg-success"><i class="bi bi-check2 me-1"></i> Accepted</span>
                                        @elseif($order->status === 'NEW')
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Pending</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $order->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->label)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                <i class="bi bi-file-earmark-pdf me-1"></i> Label Ready
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border">No Label</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            @if($order->label)
                                                <a href="{{ route('labels.view', $order->label->id) }}" target="_blank" class="btn btn-outline-primary" title="View Label PDF">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-success" onclick="printPdfLabel('{{ route('labels.view', $order->label->id) }}')" title="Print Label (4x6 AWB)">
                                                    <i class="bi bi-printer me-1"></i> Print
                                                </button>
                                            @else
                                                <form action="{{ route('meesho.process-order', $order->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-lightning-charge-fill me-1"></i> Accept & Get Label
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Print Label Modal / Hidden Iframe -->
<iframe id="labelPrintFrame" style="display:none;"></iframe>

<script>
function printPdfLabel(url) {
    const frame = document.getElementById('labelPrintFrame');
    frame.src = url;
    frame.onload = function() {
        frame.contentWindow.focus();
        frame.contentWindow.print();
    };
}
</script>
@endsection

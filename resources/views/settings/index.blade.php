@extends('layouts.app')

@section('title', 'System Settings')
@section('page-title', 'System Settings & WhatsApp API Integration')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">

        <!-- WhatsApp Gateway Status & Live QR Connection -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0"><i class="bi bi-qr-code-scan me-2"></i> WhatsApp Web API Gateway (Open-Source Automated Service)</h6>
                <span class="badge bg-light text-dark fw-bold">Port 3000</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-5 text-center border-end">
                        <div id="settingsQrBox" class="p-3 border rounded bg-light d-inline-block">
                            <div class="spinner-border text-success my-3"></div>
                            <div class="text-muted fs-7">Loading QR Code...</div>
                        </div>
                    </div>
                    <div class="col-md-7 ps-md-4">
                        <h6 class="fw-bold text-dark">Automated Background Service</h6>
                        <p class="text-muted fs-7 mb-3">
                            The Node.js WhatsApp Gateway service runs locally on port 3000. It connects directly to your WhatsApp mobile account via QR code and automatically intercepts incoming customer messages to extract Order IDs in real time.
                        </p>
                        <div class="p-3 bg-light rounded border mb-3 fs-7">
                            <div><strong>Webhook Endpoint:</strong> <code>http://127.0.0.1:8000/api/v1/whatsapp/webhook</code></div>
                            <div><strong>Meta Verify Token:</strong> <code>antigravity_token</code></div>
                            <div><strong>Gateway Status API:</strong> <code>http://127.0.0.1:3000/status</code></div>
                        </div>
                        <a href="{{ route('whatsapp.import') }}" class="btn btn-sm btn-success fw-semibold"><i class="bi bi-qr-code me-1"></i> Open Gateway QR Scanner Page</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- REST API Integration Endpoints -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-code-slash text-primary me-2"></i> REST API Endpoints for Today & Date-Wise Orders</h6>
            </div>
            <div class="card-body fs-7">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Method</th>
                                <th>Endpoint URL</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success">GET</span></td>
                                <td><code>/api/v1/orders/today</code></td>
                                <td>Get JSON list of all Order IDs created/extracted <strong>Today</strong>.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success">GET</span></td>
                                <td><code>/api/v1/orders/date-wise?date=2026-08-26</code></td>
                                <td>Get JSON list of Order IDs for a specific date or date range.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-primary">POST</span></td>
                                <td><code>/api/v1/whatsapp/webhook</code></td>
                                <td>Incoming message webhook (Auto extracts Order ID & creates order).</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-primary">POST</span></td>
                                <td><code>/api/v1/whatsapp/extract</code></td>
                                <td>On-demand Order ID regex text extraction endpoint.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- WhatsApp Account Config -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-whatsapp text-success me-2"></i> WhatsApp Business Details</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $accounts->first()->id ?? '' }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Business Account Name</label>
                        <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $accounts->first()->account_name ?? 'Vishvkarma Gifts Store') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">WhatsApp Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $accounts->first()->phone_number ?? '+919876543210') }}" required>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Settings</button>
                </form>
            </div>
        </div>

        <!-- System Information -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-cpu me-2"></i> System Architecture</h6>
            </div>
            <div class="card-body fs-7">
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Laravel Framework:</div>
                    <div class="col-8">v{{ Illuminate\Foundation\Application::VERSION }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Node.js Gateway:</div>
                    <div class="col-8">Baileys / Express Server (Port 3000)</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">PHP Version:</div>
                    <div class="col-8">{{ PHP_VERSION }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Database:</div>
                    <div class="col-8">MySQL / MariaDB (Port 3307)</div>
                </div>
                <div class="row">
                    <div class="col-4 fw-bold">Export Engines:</div>
                    <div class="col-8">CSV, Excel (UTF-8 BOM), JSON, TXT, Printable PDF</div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateSettingsQr() {
        fetch('/api/v1/whatsapp/gateway/status')
            .then(res => res.json())
            .then(data => {
                const box = document.getElementById('settingsQrBox');
                if (data.status === 'CONNECTED') {
                    box.innerHTML = `<div class="text-success p-2"><i class="bi bi-check-circle-fill display-5"></i><div class="fw-bold mt-1">Connected</div><div class="fs-8">${data.user || ''}</div></div>`;
                } else if (data.status === 'SCAN_QR' && data.qr) {
                    box.innerHTML = `<img src="${data.qr}" alt="QR" class="img-fluid" style="max-width: 150px;">`;
                } else {
                    box.innerHTML = `<div class="text-muted p-2"><i class="bi bi-power display-5"></i><div class="mt-1">Gateway Offline</div></div>`;
                }
            })
            .catch(() => {});
    }
    updateSettingsQr();
    setInterval(updateSettingsQr, 5000);
</script>
@endpush

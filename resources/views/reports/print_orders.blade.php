<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Order IDs Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 13px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom no-print">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-printer text-primary"></i> Order IDs Summary Report</h4>
            <p class="text-muted mb-0">Use print to save as PDF or print report.</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary me-2"><i class="bi bi-printer"></i> Print / Save PDF</button>
            <button onclick="window.close()" class="btn btn-outline-secondary">Close</button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-8">
            <h3 class="fw-bold text-primary mb-1">VISHVKARMA GIFTS - ORDER IDs</h3>
            <p class="text-muted mb-0">Automated WhatsApp Order Extraction & Label Matching Report</p>
        </div>
        <div class="col-4 text-end">
            <div class="fw-bold">Report Date: {{ date('Y-m-d H:i:s') }}</div>
            <div class="text-muted">Total Orders: {{ $orders->count() }}</div>
        </div>
    </div>

    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Order ID</th>
                <th>Customer Name</th>
                <th>Phone Number</th>
                <th>Status</th>
                <th>Packing</th>
                <th>Confidence</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $ord)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="fw-bold text-primary">{{ $ord->order_id }}</td>
                    <td>{{ $ord->customer_name }}</td>
                    <td>{{ $ord->phone_number }}</td>
                    <td><span class="badge bg-secondary">{{ $ord->status }}</span></td>
                    <td><span class="badge bg-light text-dark border">{{ $ord->packing_status }}</span></td>
                    <td>{{ $ord->extraction_confidence }}%</td>
                    <td>{{ $ord->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No orders found for selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

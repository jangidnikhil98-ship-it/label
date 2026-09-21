@extends('layouts.app')

@section('title', 'Import Shipping Labels')
@section('page-title', 'Import Shipping Labels')

@section('content')
<div class="row justify-content-center mb-4">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-upload text-primary me-2"></i> Upload Meesho Shipping Labels</h6>
            </div>
            <div class="card-body text-center p-4">
                <form id="labelUploadForm" enctype="multipart/form-data">
                    @csrf
                    <!-- Drag & Drop Zone -->
                    <div id="dropZone" class="border border-2 border-dashed rounded-3 p-5 bg-light mb-3 cursor-pointer">
                        <i class="bi bi-cloud-arrow-up display-3 text-primary"></i>
                        <h5 class="fw-bold mt-3">Drag & Drop files here</h5>
                        <p class="text-muted fs-7 mb-3">Supports PDF, ZIP archives, JPG, JPEG, and PNG files</p>
                        <label for="labelFilesInput" class="btn btn-outline-primary px-4"><i class="bi bi-folder2-open me-1"></i> Select Files</label>
                        <input type="file" id="labelFilesInput" name="labels[]" multiple accept=".pdf,.zip,.jpg,.jpeg,.png" class="d-none">
                    </div>

                    <div id="selectedFilesList" class="text-start mb-3 fs-7 text-muted d-none">
                        <strong>Selected Files:</strong> <span id="fileNamesText"></span>
                    </div>

                    <button type="submit" id="processBtn" class="btn btn-primary px-4 py-2 fw-semibold" disabled>
                        <i class="bi bi-gear-fill me-1"></i> Process Labels
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Processing Summary & Results -->
<div id="resultsCard" class="row justify-content-center d-none">
    <div class="col-lg-9">
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted fs-7">Total Processed</div>
                    <div id="sumTotal" class="fw-bold fs-4 text-dark">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted fs-7">Matched</div>
                    <div id="sumMatched" class="fw-bold fs-4 text-success">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted fs-7">Unmatched</div>
                    <div id="sumUnmatched" class="fw-bold fs-4 text-warning">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted fs-7">Errors</div>
                    <div id="sumErrors" class="fw-bold fs-4 text-danger">0</div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="bi bi-list-check me-2"></i> Processing Details</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-7">
                    <thead class="table-light">
                        <tr>
                            <th>File Name</th>
                            <th>Detected Order ID</th>
                            <th>Confidence</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="resultsTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const fileInput = document.getElementById('labelFilesInput');
    const dropZone = document.getElementById('dropZone');
    const processBtn = document.getElementById('processBtn');
    const fileNamesText = document.getElementById('fileNamesText');
    const selectedFilesList = document.getElementById('selectedFilesList');

    fileInput.addEventListener('change', handleFiles);

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('bg-secondary-subtle');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('bg-secondary-subtle');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('bg-secondary-subtle');
        fileInput.files = e.dataTransfer.files;
        handleFiles();
    });

    function handleFiles() {
        if (fileInput.files.length > 0) {
            const names = Array.from(fileInput.files).map(f => f.name).join(', ');
            fileNamesText.innerText = names;
            selectedFilesList.classList.remove('d-none');
            processBtn.disabled = false;
        } else {
            selectedFilesList.classList.add('d-none');
            processBtn.disabled = true;
        }
    }

    document.getElementById('labelUploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        processBtn.disabled = true;
        processBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing Labels...';

        fetch("{{ route('labels.import.process') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            processBtn.disabled = false;
            processBtn.innerHTML = '<i class="bi bi-gear-fill me-1"></i> Process Labels';

            if (data.success) {
                showToast(data.message, 'success');
                const s = data.summary;
                document.getElementById('resultsCard').classList.remove('d-none');
                document.getElementById('sumTotal').innerText = s.processed;
                document.getElementById('sumMatched').innerText = s.matched;
                document.getElementById('sumUnmatched').innerText = s.unmatched;
                document.getElementById('sumErrors').innerText = s.errors;

                const tbody = document.getElementById('resultsTableBody');
                tbody.innerHTML = '';

                s.labels.forEach(lbl => {
                    const tr = document.createElement('tr');
                    const badgeClass = lbl.status === 'LABEL_MATCHED' ? 'bg-success' : 'bg-warning text-dark';
                    tr.innerHTML = `
                        <td class="fw-bold">${lbl.file_name}</td>
                        <td>${lbl.detected_order_id || '<span class="text-muted">None</span>'}</td>
                        <td>${lbl.confidence}%</td>
                        <td><span class="badge ${badgeClass}">${lbl.status}</span></td>
                        <td>
                            <a href="/labels/${lbl.id}/view" target="_blank" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i> View</a>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                showToast(data.message || 'Processing failed.', 'danger');
            }
        });
    });
</script>
@endpush

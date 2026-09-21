@props(['status', 'type' => 'order'])

@php
    $status = strtoupper($status ?? '');
    $badgeClass = 'bg-secondary';
    $label = $status;

    if ($type === 'order') {
        switch ($status) {
            case 'NEW':
                $badgeClass = 'bg-secondary';
                break;
            case 'VERIFIED':
                $badgeClass = 'bg-primary';
                break;
            case 'ACCEPTED':
                $badgeClass = 'bg-success';
                break;
            case 'REJECTED':
                $badgeClass = 'bg-danger';
                break;
            case 'SHIPPED':
                $badgeClass = 'bg-indigo text-white';
                break;
            case 'COMPLETED':
                $badgeClass = 'bg-dark';
                break;
        }
    } elseif ($type === 'label') {
        switch ($status) {
            case 'LABEL_MATCHED':
            case 'MATCHED':
            case 'LABEL_READY':
            case 'READY':
                $badgeClass = 'bg-success';
                $label = '✓ Matched';
                break;
            case 'LABEL_PENDING':
            case 'PENDING':
                $badgeClass = 'bg-secondary';
                $label = 'Pending';
                break;
            case 'LABEL_UNMATCHED':
            case 'UNMATCHED':
                $badgeClass = 'bg-warning text-dark';
                $label = '⚠ Unmatched';
                break;
            case 'LABEL_ERROR':
            case 'ERROR':
                $badgeClass = 'bg-danger';
                $label = '✕ Error';
                break;
        }
    } elseif ($type === 'packing') {
        switch ($status) {
            case 'PACKED':
                $badgeClass = 'bg-success';
                $label = '✓ Packed';
                break;
            case 'READY':
                $badgeClass = 'bg-info text-dark';
                $label = 'Ready for Packing';
                break;
            case 'NOT_PACKED':
                $badgeClass = 'bg-secondary';
                $label = 'Not Packed';
                break;
        }
    }
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . $badgeClass]) }}>
    {{ $label }}
</span>

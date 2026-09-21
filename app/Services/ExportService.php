<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderLabel;

class ExportService
{
    /**
     * Build base Order query with filters.
     */
    protected function buildOrderQuery(array $filters)
    {
        $userId = auth()->id();
        $query = Order::where('user_id', $userId)->with(['label', 'chat', 'message']);

        if (!empty($filters['period'])) {
            $period = strtolower($filters['period']);
            if ($period === 'today') {
                $query->whereDate('created_at', date('Y-m-d'));
            } elseif ($period === 'yesterday') {
                $query->whereDate('created_at', date('Y-m-d', strtotime('-1 day')));
            } elseif ($period === '3days') {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime('-3 days')));
            } elseif ($period === '7days') {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')));
            } elseif ($period === 'month') {
                $query->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'));
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', strtoupper($filters['status']));
        }

        if (!empty($filters['packing_status'])) {
            $query->where('packing_status', strtoupper($filters['packing_status']));
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest();
    }

    /**
     * Export Orders to CSV.
     */
    public function exportOrdersCsv(array $filters = []): string
    {
        $orders = $this->buildOrderQuery($filters)->get();

        $headers = [
            'Order ID',
            'Customer Name',
            'Phone Number',
            'Order Status',
            'Label Status',
            'Packing Status',
            'Extraction Confidence',
            'Order Date',
            'Label File',
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($orders as $order) {
            fputcsv($handle, [
                $order->order_id,
                $order->customer_name,
                $order->phone_number,
                $order->status,
                $order->label_status,
                $order->packing_status,
                $order->extraction_confidence . '%',
                $order->created_at->format('Y-m-d H:i:s'),
                $order->label ? $order->label->file_name : 'N/A',
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return $csvContent;
    }

    /**
     * Export Orders to Excel-Compatible CSV (with UTF-8 BOM).
     */
    public function exportOrdersExcel(array $filters = []): string
    {
        $csv = $this->exportOrdersCsv($filters);
        // Prepend UTF-8 BOM (\xEF\xBB\xBF) so Microsoft Excel opens special characters cleanly
        return "\xEF\xBB\xBF" . $csv;
    }

    /**
     * Export Orders to JSON.
     */
    public function exportOrdersJson(array $filters = []): string
    {
        $orders = $this->buildOrderQuery($filters)->get();

        $data = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_id' => $order->order_id,
                'customer_name' => $order->customer_name,
                'phone_number' => $order->phone_number,
                'status' => $order->status,
                'packing_status' => $order->packing_status,
                'extraction_confidence' => $order->extraction_confidence,
                'created_at' => $order->created_at->toIso8601String(),
                'whatsapp_chat' => $order->chat ? [
                    'id' => $order->chat->id,
                    'customer_name' => $order->chat->customer_name,
                    'phone_number' => $order->chat->phone_number,
                ] : null,
                'label' => $order->label ? [
                    'id' => $order->label->id,
                    'file_name' => $order->label->file_name,
                    'status' => $order->label->status,
                ] : null,
            ];
        });

        return json_encode([
            'exported_at' => now()->toIso8601String(),
            'total_orders' => $data->count(),
            'orders' => $data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Export Orders to Clean TXT format.
     */
    public function exportOrdersTxt(array $filters = []): string
    {
        $orders = $this->buildOrderQuery($filters)->get();

        $lines = [];
        $lines[] = "==================================================";
        $lines[] = "           EXTRACTED ORDER IDs REPORT            ";
        $lines[] = "Generated On: " . date('Y-m-d H:i:s');
        $lines[] = "Total Orders: " . $orders->count();
        $lines[] = "==================================================\n";

        foreach ($orders as $index => $order) {
            $num = $index + 1;
            $lines[] = "{$num}. ORDER ID: {$order->order_id}";
            $lines[] = "   Customer: {$order->customer_name}";
            $lines[] = "   Phone: {$order->phone_number}";
            $lines[] = "   Status: {$order->status} | Packing: {$order->packing_status}";
            $lines[] = "   Date: " . $order->created_at->format('Y-m-d H:i:s');
            $lines[] = "   ----------------------------------------------";
        }

        return implode("\n", $lines);
    }

    /**
     * Export Labels to CSV.
     */
    public function exportLabelsCsv(array $filters = []): string
    {
        $userId = auth()->id();
        $query = OrderLabel::where('user_id', $userId)->with('order');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('detected_order_id', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', strtoupper($filters['status']));
        }

        $labels = $query->latest()->get();

        $headers = [
            'ID',
            'File Name',
            'File Type',
            'Detected Order ID',
            'Matched Order ID',
            'Confidence',
            'Status',
            'Processed At',
            'Created At',
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($labels as $label) {
            fputcsv($handle, [
                $label->id,
                $label->file_name,
                $label->file_type,
                $label->detected_order_id ?: 'N/A',
                $label->order ? $label->order->order_id : 'N/A',
                $label->confidence . '%',
                $label->status,
                $label->processed_at ? $label->processed_at->format('Y-m-d H:i:s') : 'N/A',
                $label->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return $csvContent;
    }
}

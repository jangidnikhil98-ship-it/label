<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLabel;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(
        protected ExportService $exportService
    ) {}

    public function orderReport(Request $request)
    {
        $userId = auth()->id();
        $query = Order::where('user_id', $userId)->with('label');

        if ($request->filled('period')) {
            $period = strtolower($request->period);
            if ($period === 'today') {
                $query->whereDate('created_at', date('Y-m-d'));
            } elseif ($period === 'yesterday') {
                $query->whereDate('created_at', date('Y-m-d', strtotime('-1 day')));
            } elseif ($period === '3days') {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime('-3 days')));
            } elseif ($period === '7days') {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')));
            }
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('reports.orders', compact('orders'));
    }

    public function labelReport(Request $request)
    {
        $userId = auth()->id();
        $query = OrderLabel::where('user_id', $userId)->with('order');

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $labels = $query->latest()->paginate(20)->withQueryString();

        return view('reports.labels', compact('labels'));
    }

    public function exportOrdersCsv(Request $request)
    {
        $csvData = $this->exportService->exportOrdersCsv($request->all());
        $filename = 'orders_report_' . date('Y_m_d_His') . '.csv';

        return response($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportOrdersExcel(Request $request)
    {
        $excelData = $this->exportService->exportOrdersExcel($request->all());
        $filename = 'orders_report_' . date('Y_m_d_His') . '.csv';

        return response($excelData, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportOrdersJson(Request $request)
    {
        $jsonData = $this->exportService->exportOrdersJson($request->all());
        $filename = 'orders_report_' . date('Y_m_d_His') . '.json';

        return response($jsonData, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportOrdersTxt(Request $request)
    {
        $txtData = $this->exportService->exportOrdersTxt($request->all());
        $filename = 'orders_report_' . date('Y_m_d_His') . '.txt';

        return response($txtData, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function printOrdersPdf(Request $request)
    {
        $userId = auth()->id();
        $query = Order::where('user_id', $userId)->with(['label', 'chat', 'message']);

        if ($request->filled('period')) {
            $period = strtolower($request->period);
            if ($period === 'today') {
                $query->whereDate('created_at', date('Y-m-d'));
            } elseif ($period === 'yesterday') {
                $query->whereDate('created_at', date('Y-m-d', strtotime('-1 day')));
            } elseif ($period === '3days') {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime('-3 days')));
            } elseif ($period === '7days') {
                $query->whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->get();

        return view('reports.print_orders', compact('orders'));
    }

    public function exportLabelsCsv(Request $request)
    {
        $csvData = $this->exportService->exportLabelsCsv($request->all());
        $filename = 'labels_report_' . date('Y_m_d_His') . '.csv';

        return response($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

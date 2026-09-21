<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $query = Order::where('user_id', $userId)->with(['label', 'chat', 'message']);

        // Count today's and last 3 days orders
        $todayCount = Order::where('user_id', $userId)->whereDate('created_at', date('Y-m-d'))->count();
        $last3DaysCount = Order::where('user_id', $userId)->whereDate('created_at', '>=', date('Y-m-d', strtotime('-3 days')))->count();

        // Quick period filter
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
            } elseif ($period === 'month') {
                $query->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'));
            }
        }

        // Quick tab filter if provided
        if ($request->filled('tab')) {
            $tab = strtoupper($request->tab);
            if (in_array($tab, ['NEW', 'VERIFIED', 'ACCEPTED', 'REJECTED', 'COMPLETED'])) {
                $query->where('status', $tab);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        // Label Status Filter
        if ($request->filled('label_status')) {
            $labelStatus = strtoupper($request->label_status);
            if ($labelStatus === 'MATCHED') {
                $query->whereHas('label', fn($q) => $q->where('status', 'LABEL_MATCHED'));
            } elseif ($labelStatus === 'PENDING') {
                $query->whereDoesntHave('label');
            }
        }

        // Date Filters
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('orders.index', compact('orders', 'todayCount', 'last3DaysCount'));
    }

    public function show(Order $order)
    {
        if ($order->user_id && $order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $order->load(['label', 'chat', 'message']);
        return view('orders.show', compact('order'));
    }

    public function updateStatus(UpdateOrderRequest $request, Order $order)
    {
        if ($order->user_id && $order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $data = [];

        if ($request->filled('status')) {
            $newStatus = strtoupper($request->status);
            $data['status'] = $newStatus;

            if ($newStatus === 'VERIFIED') {
                $data['verified_at'] = now();
            } elseif ($newStatus === 'ACCEPTED') {
                $data['accepted_at'] = now();
                if ($order->label && $order->label->status === 'LABEL_MATCHED') {
                    $data['packing_status'] = 'READY';
                }
            } elseif ($newStatus === 'COMPLETED') {
                $data['completed_at'] = now();
            }
        }

        if ($request->filled('packing_status')) {
            $data['packing_status'] = strtoupper($request->packing_status);
            if ($data['packing_status'] === 'PACKED') {
                $data['packed_at'] = now();
            }
        }

        $order->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully.',
                'order' => $order->fresh(['label']),
            ]);
        }

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }

    public function bulkAction(Request $request)
    {
        $userId = auth()->id();
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
            'action' => 'required|string|in:mark_packed,mark_accepted,mark_verified',
        ]);

        $orders = Order::where('user_id', $userId)->whereIn('id', $request->order_ids)->get();

        foreach ($orders as $order) {
            if ($request->action === 'mark_packed') {
                $order->update([
                    'packing_status' => 'PACKED',
                    'packed_at' => now(),
                    'status' => 'SHIPPED',
                ]);
            } elseif ($request->action === 'mark_accepted') {
                $order->update([
                    'status' => 'ACCEPTED',
                    'accepted_at' => now(),
                    'packing_status' => ($order->label && $order->label->status === 'LABEL_MATCHED') ? 'READY' : $order->packing_status,
                ]);
            } elseif ($request->action === 'mark_verified') {
                $order->update([
                    'status' => 'VERIFIED',
                    'verified_at' => now(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk operation executed successfully.',
        ]);
    }
}

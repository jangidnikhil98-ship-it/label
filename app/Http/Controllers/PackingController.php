<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PackingController extends Controller
{
    /**
     * Display Packing Mode UI.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $currentOrderId = $request->get('order_id');

        if ($currentOrderId) {
            $currentOrder = Order::where('user_id', $userId)->with('label')->where('id', $currentOrderId)->first();
        } else {
            $currentOrder = Order::where('user_id', $userId)
                ->with('label')
                ->where('packing_status', '!=', 'PACKED')
                ->whereHas('label', fn($q) => $q->where('status', 'LABEL_MATCHED'))
                ->orderBy('id', 'asc')
                ->first();

            // Fallback to any order with matched label if none ready
            if (!$currentOrder) {
                $currentOrder = Order::where('user_id', $userId)
                    ->with('label')
                    ->whereHas('label', fn($q) => $q->where('status', 'LABEL_MATCHED'))
                    ->where('packing_status', '!=', 'PACKED')
                    ->orderBy('id', 'asc')
                    ->first();
            }
        }

        // Count pending packing items for current user
        $remainingCount = Order::where('user_id', $userId)
            ->where('packing_status', '!=', 'PACKED')
            ->whereHas('label', fn($q) => $q->where('status', 'LABEL_MATCHED'))
            ->count();

        $packedTodayCount = Order::where('user_id', $userId)
            ->where('packing_status', 'PACKED')
            ->whereDate('packed_at', today())
            ->count();

        return view('packing.index', compact('currentOrder', 'remainingCount', 'packedTodayCount'));
    }

    /**
     * Mark order as packed and retrieve next order for packing.
     */
    public function markPackedAndNext(Request $request, Order $order)
    {
        $userId = auth()->id();
        if ($order->user_id && $order->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }

        $order->update([
            'packing_status' => 'PACKED',
            'packed_at' => now(),
            'status' => 'SHIPPED',
        ]);

        // Find next order in sequence
        $nextOrder = Order::where('user_id', $userId)
            ->with('label')
            ->where('id', '>', $order->id)
            ->where('packing_status', '!=', 'PACKED')
            ->whereHas('label', fn($q) => $q->where('status', 'LABEL_MATCHED'))
            ->orderBy('id', 'asc')
            ->first();

        if (!$nextOrder) {
            // Fallback to first remaining order
            $nextOrder = Order::where('user_id', $userId)
                ->with('label')
                ->where('packing_status', '!=', 'PACKED')
                ->whereHas('label', fn($q) => $q->where('status', 'LABEL_MATCHED'))
                ->orderBy('id', 'asc')
                ->first();
        }

        $remainingCount = Order::where('user_id', $userId)
            ->where('packing_status', '!=', 'PACKED')
            ->whereHas('label', fn($q) => $q->where('status', 'LABEL_MATCHED'))
            ->count();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Order {$order->order_id} marked as packed!",
                'has_next' => $nextOrder !== null,
                'next_order' => $nextOrder ? [
                    'id' => $nextOrder->id,
                    'order_id' => $nextOrder->order_id,
                    'customer_name' => $nextOrder->customer_name,
                    'phone_number' => $nextOrder->phone_number,
                    'status' => $nextOrder->status,
                    'packing_status' => $nextOrder->packing_status,
                    'label_url' => $nextOrder->label ? route('labels.view', $nextOrder->label->id) : null,
                    'label_download_url' => $nextOrder->label ? route('labels.download', $nextOrder->label->id) : null,
                ] : null,
                'remaining_count' => $remainingCount,
            ]);
        }

        if ($nextOrder) {
            return redirect()->route('packing.index', ['order_id' => $nextOrder->id])
                ->with('success', "Order {$order->order_id} packed. Next order loaded.");
        }

        return redirect()->route('packing.index')->with('success', "Order {$order->order_id} packed. All ready orders packed!");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLabel;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $todaysOrdersCount = Order::where('user_id', $userId)->whereDate('created_at', today())->count();
        $last3DaysOrdersCount = Order::where('user_id', $userId)->whereDate('created_at', '>=', date('Y-m-d', strtotime('-3 days')))->count();
        $newOrdersCount = Order::where('user_id', $userId)->where('status', 'NEW')->count();
        $pendingVerificationCount = Order::where('user_id', $userId)->where('status', 'VERIFIED')->count();
        $acceptedOrdersCount = Order::where('user_id', $userId)->where('status', 'ACCEPTED')->count();

        $labelsPendingCount = OrderLabel::where('user_id', $userId)->where('status', 'LABEL_PENDING')->count();
        $labelsMatchedCount = OrderLabel::where('user_id', $userId)->where('status', 'LABEL_MATCHED')->count();

        $readyPackingCount = Order::where('user_id', $userId)->where('packing_status', 'READY')->count();
        $packedCount = Order::where('user_id', $userId)->where('packing_status', 'PACKED')->count();

        // Tables / lists
        $recentOrders = Order::where('user_id', $userId)->with('label')->latest()->take(10)->get();

        $attentionOrders = Order::where('user_id', $userId)->with('label')
            ->where(function ($q) {
                $q->where('status', 'NEW')
                  ->orWhere('status', 'VERIFIED')
                  ->orWhereDoesntHave('label');
            })
            ->latest()
            ->take(10)
            ->get();

        $unmatchedLabels = OrderLabel::where('user_id', $userId)->where('status', 'LABEL_UNMATCHED')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.index', compact(
            'todaysOrdersCount',
            'last3DaysOrdersCount',
            'newOrdersCount',
            'pendingVerificationCount',
            'acceptedOrdersCount',
            'labelsPendingCount',
            'labelsMatchedCount',
            'readyPackingCount',
            'packedCount',
            'recentOrders',
            'attentionOrders',
            'unmatchedLabels'
        ));
    }
}

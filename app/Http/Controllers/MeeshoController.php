<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MeeshoBotService;
use Illuminate\Http\Request;

class MeeshoController extends Controller
{
    public function __construct(
        protected MeeshoBotService $meeshoBotService
    ) {}

    /**
     * Display Meesho RPA Dashboard
     */
    public function index()
    {
        $userId = auth()->id();
        $botStatus = $this->meeshoBotService->getStatus();

        // Fetch recent orders that match Meesho pattern
        $allOrders = Order::where('user_id', $userId)
            ->with(['label', 'chat'])
            ->latest()
            ->take(50)
            ->get();

        $meeshoOrders = $allOrders->filter(function ($order) {
            return $this->meeshoBotService->isMeeshoOrderId($order->order_id);
        });

        return view('meesho.index', compact('botStatus', 'meeshoOrders'));
    }

    /**
     * Connect or save session
     */
    public function connect(Request $request)
    {
        if ($request->has('launch_browser')) {
            $result = $this->meeshoBotService->launchBrowserLogin();
            if (!empty($result['success'])) {
                return redirect()->back()->with('success', $result['message']);
            }
            return redirect()->back()->with('error', $result['error'] ?? 'Failed to open Chrome login window.');
        }

        $request->validate([
            'cookies_or_token' => 'required|string',
        ]);

        $raw = trim($request->input('cookies_or_token'));
        $payload = [
            'accountName' => $request->input('account_name', 'Meesho Seller'),
            'supplierId' => $request->input('supplier_id', null),
        ];

        // Check if raw is JSON cookies or auth token string
        if (str_starts_with($raw, '[') || str_starts_with($raw, '{')) {
            $parsed = json_decode($raw, true);
            $payload['cookies'] = $parsed;
        } else {
            $payload['authToken'] = $raw;
        }

        $result = $this->meeshoBotService->saveSession($payload);

        if (!empty($result['success'])) {
            return redirect()->back()->with('success', 'Meesho session saved successfully!');
        }

        return redirect()->back()->with('error', $result['error'] ?? 'Could not save session.');
    }

    /**
     * Accept order on Meesho and download shipping label
     */
    public function processOrder(Order $order)
    {
        $result = $this->meeshoBotService->processOrder($order, autoAccept: true, downloadLabel: true);

        if (!empty($result['success'])) {
            $msg = "Order {$order->order_id} successfully processed! ";
            if (!empty($result['awb'])) {
                $msg .= "AWB: {$result['awb']}. ";
            }
            $msg .= "Shipping label ready for printing.";
            return redirect()->back()->with('success', $msg);
        }

        return redirect()->back()->with('error', 'Meesho processing failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Clear active session
     */
    public function logout()
    {
        $this->meeshoBotService->logout();
        return redirect()->back()->with('success', 'Meesho session cleared.');
    }
}

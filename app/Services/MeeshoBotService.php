<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderLabel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeeshoBotService
{
    protected string $botUrl;

    public function __construct()
    {
        $this->botUrl = rtrim(config('services.meesho.bot_url', 'http://127.0.0.1:3001'), '/');
    }

    /**
     * Check if an order ID looks like a Meesho Sub-Order ID.
     * Meesho Sub-Order IDs are typically:
     * - Numerical digits 9-16 digits long (e.g. 338440392393)
     * - Numerical with suffix _1, _2 (e.g. 338440392393_1, 4089238472_1)
     * - Prefixed with MS-, MEESHO-, M- (e.g. MS-92837482)
     */
    public function isMeeshoOrderId(string $orderId): bool
    {
        $cleaned = trim($orderId);

        // Pattern 1: Alphanumeric prefix
        if (preg_match('/^(?:MS|MEESHO|MEE)[\-_]?[0-9A-Z\-_]{4,25}$/i', $cleaned)) {
            return true;
        }

        // Pattern 2: Numerical with sub-order suffix (e.g. 338440392393_1)
        if (preg_match('/^\d{8,16}(?:_\d+)?$/', $cleaned)) {
            return true;
        }

        return false;
    }

    /**
     * Get connection status from Meesho RPA Bot
     */
    public function getStatus(): array
    {
        try {
            $response = Http::timeout(4)->get("{$this->botUrl}/api/meesho/status");
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('Meesho Bot connection check failed: ' . $e->getMessage());
        }

        return [
            'connected' => false,
            'message' => 'Meesho Bot service offline or not responding on ' . $this->botUrl,
            'chromeAvailable' => false,
        ];
    }

    /**
     * Save manual session cookies or auth token
     */
    public function saveSession(array $payload): array
    {
        try {
            $response = Http::timeout(8)->post("{$this->botUrl}/api/meesho/session", $payload);
            return $response->json();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Could not connect to Meesho Bot: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Launch browser for interactive seller OTP login
     */
    public function launchBrowserLogin(): array
    {
        try {
            $response = Http::timeout(20)->post("{$this->botUrl}/api/meesho/login/launch");
            return $response->json();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Could not launch login browser: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Clear active Meesho session
     */
    public function logout(): array
    {
        try {
            $response = Http::timeout(5)->post("{$this->botUrl}/api/meesho/logout");
            return $response->json();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Accept order on Meesho and download the shipping label
     */
    public function processOrder(Order $order, bool $autoAccept = true, bool $downloadLabel = true): array
    {
        try {
            $response = Http::timeout(45)->post("{$this->botUrl}/api/meesho/orders/process", [
                'orderId' => $order->order_id,
                'autoAccept' => $autoAccept,
                'downloadLabel' => $downloadLabel,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['success'])) {
                    // Update Order Status
                    $order->update([
                        'status' => 'ACCEPTED',
                        'accepted_at' => now(),
                    ]);

                    // Link or Create OrderLabel
                    if (!empty($data['labelRelativePath'])) {
                        $label = OrderLabel::updateOrCreate(
                            ['order_id' => $order->id],
                            [
                                'user_id' => $order->user_id ?? auth()->id(),
                                'file_path' => $data['labelRelativePath'],
                                'file_name' => $data['labelFileName'] ?? basename($data['labelRelativePath']),
                                'file_type' => 'pdf',
                                'detected_order_id' => $order->order_id,
                                'confidence' => 100.00,
                                'status' => 'LABEL_READY',
                                'processed_at' => now(),
                            ]
                        );

                        $data['label_id'] = $label->id;
                    }

                    return $data;
                }
            }

            return [
                'success' => false,
                'error' => $response->json('error') ?? 'Unknown error from Meesho Bot',
            ];
        } catch (\Exception $e) {
            Log::error("MeeshoBotService error processing order {$order->order_id}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Bot communication failure: ' . $e->getMessage(),
            ];
        }
    }
}

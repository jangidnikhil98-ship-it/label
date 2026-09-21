<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.gateway_url', 'http://127.0.0.1:3000');
    }

    /**
     * Get gateway status and live QR code.
     */
    public function getStatus(): array
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/status");
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('WhatsApp Gateway unreachable: ' . $e->getMessage());
        }

        return [
            'success' => false,
            'status' => 'OFFLINE',
            'qr' => null,
            'user' => null,
            'message' => 'Node.js WhatsApp Gateway service is offline.',
        ];
    }

    /**
     * Trigger manual sync for specific number of past days (default 3) or date range.
     */
    public function syncMessages(int $days = 3, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        try {
            $response = Http::timeout(60)->post("{$this->baseUrl}/sync", [
                'days' => $days,
                'date_from' => $dateFrom ?: date('Y-m-d', strtotime("-{$days} days")),
                'date_to' => $dateTo ?: date('Y-m-d'),
            ]);
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('WhatsApp Gateway sync error: ' . $e->getMessage());
        }

        return [
            'success' => false,
            'message' => 'Failed to reach WhatsApp Gateway service.',
        ];
    }

    /**
     * Logout and disconnect WhatsApp session.
     */
    public function disconnect(): array
    {
        try {
            $response = Http::timeout(5)->post("{$this->baseUrl}/disconnect");
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('WhatsApp Gateway disconnect error: ' . $e->getMessage());
        }

        return [
            'success' => false,
            'message' => 'Failed to disconnect WhatsApp Gateway.',
        ];
    }
}

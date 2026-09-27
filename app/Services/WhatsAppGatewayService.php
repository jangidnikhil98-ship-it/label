<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    protected string $baseUrl;

    public function __construct(
        protected OrderExtractionService $extractionService
    ) {
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
    public function syncMessages(int $days = 3, ?string $dateFrom = null, ?string $dateTo = null, ?int $userId = null): array
    {
        $userId = $userId ?: (auth()->id() ?: 1);

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/sync", [
                'days' => $days,
                'date_from' => $dateFrom ?: date('Y-m-d', strtotime("-{$days} days")),
                'date_to' => $dateTo ?: date('Y-m-d'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $messages = $data['messages'] ?? [];

                $results = [
                    'total_received' => count($messages),
                    'orders_detected' => 0,
                    'orders_created' => 0,
                    'duplicates_skipped' => 0,
                ];

                foreach ($messages as $msgData) {
                    $text = $msgData['message_text'] ?? '';
                    $imagePath = $msgData['image_path'] ?? null;
                    if (empty(trim($text)) && empty($imagePath)) {
                        continue;
                    }

                    $senderPhone = $msgData['sender_phone'] ?? null;
                    $senderName = $msgData['sender_name'] ?? 'Customer';
                    $messageId = $msgData['message_id'] ?? null;
                    $timestamp = $msgData['timestamp'] ?? null;

                    $extractionResult = $this->extractionService->processImport(
                        $text ?: '',
                        $senderName,
                        $imagePath,
                        $userId,
                        $senderPhone,
                        $timestamp,
                        $messageId,
                        'whatsapp_sync'
                    );

                    if (!empty($extractionResult['parsed']['order_id'])) {
                        $results['orders_detected']++;

                        if ($extractionResult['is_duplicate']) {
                            $results['duplicates_skipped']++;
                        } else {
                            $this->extractionService->createOrderFromExtraction($extractionResult);
                            $results['orders_created']++;
                        }
                    }
                }

                return [
                    'success' => true,
                    'days' => $days,
                    'message' => "Sync completed for the last {$days} days! Processed " . count($messages) . " WhatsApp message(s), created {$results['orders_created']} new order(s), {$results['duplicates_skipped']} duplicate(s) skipped.",
                    'processed_messages' => count($messages),
                    'orders_created' => $results['orders_created'],
                    'duplicates_skipped' => $results['duplicates_skipped'],
                ];
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderExtractionService;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppApiController extends Controller
{
    public function __construct(
        protected OrderExtractionService $extractionService,
        protected WhatsAppGatewayService $gatewayService
    ) {}

    /**
     * Meta Cloud API Verification Handshake (GET).
     */
    public function verifyWebhook(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token', 'antigravity_token');
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response()->json(['error' => 'Unauthorized verification token.'], 403);
    }

    /**
     * Handle incoming WhatsApp Webhook payload (POST).
     * Accepts payload from Node.js WhatsApp Gateway, Meta Cloud API, or Twilio.
     */
    public function handleWebhook(Request $request)
    {
        try {
            $senderPhone = $request->input('sender_phone') ?: $request->input('From');
            $senderName = $request->input('sender_name') ?: $request->input('ProfileName', 'Customer');
            $messageText = $request->input('message_text') ?: $request->input('Body', '');

            if (empty($messageText) && $request->has('entry')) {
                // Meta Cloud API payload format
                $entry = $request->input('entry.0.changes.0.value');
                if (!empty($entry['messages'][0])) {
                    $msg = $entry['messages'][0];
                    $senderPhone = $msg['from'] ?? null;
                    $senderName = $entry['contacts'][0]['profile']['name'] ?? 'Customer';
                    $messageText = $msg['text']['body'] ?? $msg['caption'] ?? '';
                }
            }

            if (empty($messageText)) {
                return response()->json(['success' => false, 'message' => 'No message text found in payload.'], 400);
            }

            $messageId = $request->input('message_id');
            $timestamp = $request->input('timestamp');

            $extractionResult = $this->extractionService->processImport(
                $messageText,
                $senderName,
                null,
                auth()->id() ?: 1,
                $senderPhone,
                $timestamp,
                $messageId,
                'whatsapp_webhook'
            );

            if (!$extractionResult['parsed']['order_id']) {
                return response()->json([
                    'success' => true,
                    'order_detected' => false,
                    'message' => 'Message received, no Order ID matched.',
                ]);
            }

            $order = $this->extractionService->createOrderFromExtraction($extractionResult);

            return response()->json([
                'success' => true,
                'order_detected' => true,
                'is_duplicate' => $extractionResult['is_duplicate'],
                'order_id' => $order->order_id,
                'order' => $order->load(['chat', 'message', 'label']),
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp Webhook: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle incoming batch of historical/synced WhatsApp messages.
     */
    public function handleBatchWebhook(Request $request)
    {
        try {
            $messages = $request->input('messages', []);
            if (!is_array($messages) || empty($messages)) {
                return response()->json(['success' => false, 'message' => 'No messages provided.'], 400);
            }

            $userId = auth()->id() ?: 1;
            $results = [
                'total_received' => count($messages),
                'orders_detected' => 0,
                'orders_created' => 0,
                'duplicates_skipped' => 0,
                'orders' => [],
            ];

            foreach ($messages as $msgData) {
                $text = $msgData['message_text'] ?? '';
                if (empty(trim($text))) {
                    continue;
                }

                $senderPhone = $msgData['sender_phone'] ?? null;
                $senderName = $msgData['sender_name'] ?? 'Customer';
                $messageId = $msgData['message_id'] ?? null;
                $timestamp = $msgData['timestamp'] ?? null;

                $extractionResult = $this->extractionService->processImport(
                    $text,
                    $senderName,
                    null,
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
                        $results['orders'][] = [
                            'order_id' => $extractionResult['parsed']['order_id'],
                            'is_duplicate' => true,
                            'order' => $extractionResult['existing_order'],
                        ];
                    } else {
                        $order = $this->extractionService->createOrderFromExtraction($extractionResult);
                        $results['orders_created']++;
                        $results['orders'][] = [
                            'order_id' => $order->order_id,
                            'is_duplicate' => false,
                            'order' => $order->load(['chat', 'message', 'label']),
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Batch sync processed {$results['total_received']} message(s): {$results['orders_created']} order(s) created, {$results['duplicates_skipped']} duplicate(s) skipped.",
                'summary' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing batch WhatsApp Webhook: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Get Today's Extracted Order IDs.
     */
    public function getTodayOrders(Request $request)
    {
        $userId = auth()->id() ?: 1;
        $orders = Order::whereDate('created_at', date('Y-m-d'))
            ->with(['label', 'chat'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'period' => 'today',
            'date' => date('Y-m-d'),
            'total' => $orders->count(),
            'orders' => $orders,
        ]);
    }

    /**
     * API: Get Order IDs Date-Wise.
     */
    public function getOrdersByDate(Request $request)
    {
        $userId = auth()->id() ?: 1;
        $query = Order::with(['label', 'chat']);

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } elseif ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereDate('created_at', '>=', $request->date_from)
                  ->whereDate('created_at', '<=', $request->date_to);
        } elseif ($request->filled('period')) {
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

        $orders = $query->latest()->get();

        return response()->json([
            'success' => true,
            'total' => $orders->count(),
            'orders' => $orders,
        ]);
    }

    /**
     * API: Extract Order ID from payload text directly.
     */
    public function extract(Request $request)
    {
        $request->validate(['text' => 'required|string']);
        $result = $this->extractionService->processImport($request->text);

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    /**
     * API: Get WhatsApp Gateway status and live QR code.
     */
    public function getGatewayStatus()
    {
        return response()->json($this->gatewayService->getStatus());
    }

    /**
     * API: Trigger Gateway Sync.
     */
    public function triggerGatewaySync(Request $request)
    {
        $days = (int)($request->input('days', 3));
        return response()->json($this->gatewayService->syncMessages($days, $request->date_from, $request->date_to));
    }

    /**
     * API: Disconnect WhatsApp Gateway.
     */
    public function disconnectGateway()
    {
        return response()->json($this->gatewayService->disconnect());
    }
}

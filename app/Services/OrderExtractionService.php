<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderLabel;
use App\Models\WhatsAppChat;
use App\Models\WhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class OrderExtractionService
{
    public function __construct(
        protected WhatsAppService $whatsAppService,
        protected OcrService $ocrService,
        protected MeeshoBotService $meeshoBotService
    ) {}

    /**
     * Extract Order ID and Phone from text content (single primary match).
     */
    public function parseMessageText(string $text): array
    {
        // Strip invisible WhatsApp LTR/RTL/Space Unicode characters
        $text = preg_replace('/[\x{200B}-\x{200D}\x{200E}\x{200F}\x{202F}\x{FEFF}]/u', '', $text);

        $orderId = null;
        $phone = null;
        $customerName = null;

        // Order ID Regex Patterns
        $orderIdPatterns = [
            '/(?:meesho\s*(?:order|id|no)?|sub[\-_]?order|order\s*id|order\s*no|order\s*number|order|ord)[\s\:\-\#]*(?:is\s*)?([a-zA-Z0-9\-_]{4,25})/i',
            '/\b((?:OD|ORD|ORDER|MS|MEESHO|FK|AJ|AMZ)[\-_]?[0-9A-Z\-_]{4,25})\b/i',
            '/\b([0-9]{8,18}_[0-9]{1,4})\b/', // Meesho Sub-Order IDs (e.g. 9988776655_1)
            '/\b([0-9]{9,20})\b/', // Numerical Order IDs (e.g. 4089238472)
            '/#([0-9A-Z\-_]{4,15})\b/i', // Shopify / Custom hashtag Order IDs e.g. #1001
        ];

        foreach ($orderIdPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $candidate = trim($matches[1]);
                // Ensure candidate contains at least one digit and is not a phone number
                if (!preg_match('/\d/', $candidate)) {
                    continue;
                }
                if (strlen($candidate) < 10 || !preg_match('/^[6-9]\d{9}$/', $candidate)) {
                    $orderId = strtoupper($candidate);
                    break;
                }
            }
        }

        // Phone Number Regex Patterns (Indian phone numbers)
        $phonePatterns = [
            '/(?:\+91[\-\s]?)?([6-9]\d{9})\b/',
            '/(?:mobile|phone|whatsapp|contact|num|number)[\s\:\-\#]*(?:\+91[\-\s]?)?([0-9]{10})/i',
        ];

        foreach ($phonePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $phone = $this->whatsAppService->normalizePhoneNumber($matches[1]);
                break;
            }
        }

        // Customer Name extraction
        if (preg_match('/(?:name|customer|client|from)[\s\:\-\#]*([a-zA-Z\s]{2,30})/i', $text, $matches)) {
            $customerName = trim($matches[1]);
        }

        return [
            'order_id' => $orderId,
            'phone_number' => $phone,
            'customer_name' => $customerName,
            'confidence' => $orderId && $phone ? 100.00 : ($orderId ? 80.00 : 0.00),
        ];
    }

    /**
     * Extract ALL Order IDs from a multi-line or bulk text block.
     */
    public function extractAllOrderIdsFromText(string $text): array
    {
        $text = preg_replace('/[\x{200B}-\x{200D}\x{200E}\x{200F}\x{202F}\x{FEFF}]/u', '', $text);
        $orderIds = [];

        $patterns = [
            '/\b((?:OD|ORD|ORDER|MS|MEESHO|FK|AJ|AMZ)[\-_]?[0-9A-Z\-_]{4,25})\b/i',
            '/(?:order\s*id|order\s*no|order\s*number|ord|id)[\s\:\-\#]*(?:is\s*)?([a-zA-Z0-9\-_]{4,25})/i',
            '/\b([0-9]{9,20})\b/',
            '/#([0-9A-Z\-_]{4,15})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $candidate) {
                    $candidate = strtoupper(trim($candidate));
                    if (!preg_match('/\d/', $candidate)) {
                        continue;
                    }
                    if (strlen($candidate) < 10 || !preg_match('/^[6-9]\d{9}$/', $candidate)) {
                        $orderIds[] = $candidate;
                    }
                }
            }
        }

        return array_values(array_unique($orderIds));
    }

    /**
     * Process imported text or image and return extraction results.
     */
    public function processImport(
        string $rawText,
        ?string $customerName = null,
        ?string $imagePath = null,
        ?int $userId = null,
        ?string $senderPhone = null,
        mixed $messageTimestamp = null,
        ?string $externalMessageId = null,
        string $source = 'whatsapp_manual'
    ): array {
        $userId = $userId ?: auth()->id();
        $textToParse = $rawText;

        // 1. First attempt: parse order details from message text/caption
        $parsed = $this->parseMessageText($textToParse);

        // 2. Condition: If no Order ID found in text, but an image was attached, run OCR on the image!
        if (empty($parsed['order_id']) && !empty($imagePath)) {
            $ocrText = $this->ocrService->extractTextFromImage($imagePath);
            if (!empty($ocrText)) {
                $imageParsed = $this->parseMessageText($ocrText);
                if (!empty($imageParsed['order_id'])) {
                    $parsed['order_id'] = $imageParsed['order_id'];
                    $parsed['confidence'] = 90.00;
                }
                if (empty($parsed['phone_number']) && !empty($imageParsed['phone_number'])) {
                    $parsed['phone_number'] = $imageParsed['phone_number'];
                }
                if (empty($parsed['customer_name']) && !empty($imageParsed['customer_name'])) {
                    $parsed['customer_name'] = $imageParsed['customer_name'];
                }
            }
        }

        // If no phone was in text, use sender's WhatsApp phone if provided
        if (empty($parsed['phone_number']) && !empty($senderPhone)) {
            $parsed['phone_number'] = $this->whatsAppService->normalizePhoneNumber($senderPhone);
        }

        if ($customerName && empty($parsed['customer_name'])) {
            $parsed['customer_name'] = $customerName;
        }

        if (empty($parsed['customer_name'])) {
            $parsed['customer_name'] = $parsed['phone_number'] ? 'Customer ' . substr($parsed['phone_number'], -4) : 'Guest Customer';
        }

        // Check Duplicate Order ID
        $existingOrder = null;
        if ($parsed['order_id']) {
            $existingOrder = Order::where('order_id', strtoupper($parsed['order_id']))->first();
        }

        return [
            'parsed' => $parsed,
            'user_id' => $userId,
            'is_duplicate' => $existingOrder !== null,
            'existing_order' => $existingOrder,
            'raw_text' => $rawText,
            'image_path' => $imagePath,
            'sender_phone' => $senderPhone,
            'timestamp' => $messageTimestamp,
            'external_message_id' => $externalMessageId,
            'source' => $source,
        ];
    }

    /**
     * Create an order record from extracted data.
     */
    public function createOrderFromExtraction(array $extractionResult): Order
    {
        $parsed = $extractionResult['parsed'];
        $userId = $extractionResult['user_id'] ?? auth()->id();

        if ($extractionResult['is_duplicate'] && $extractionResult['existing_order']) {
            return $extractionResult['existing_order'];
        }

        $order = DB::transaction(function () use ($parsed, $extractionResult, $userId) {
            $phone = $parsed['phone_number'] ?: '0000000000';
            $chat = $this->whatsAppService->findOrCreateChat($phone, $parsed['customer_name'], $userId);

            // Determine original message timestamp
            $messageDate = now();
            if (!empty($extractionResult['timestamp'])) {
                $rawTs = $extractionResult['timestamp'];
                if (is_numeric($rawTs)) {
                    $numericTs = (int)$rawTs;
                    // If timestamp is in milliseconds, convert to seconds
                    if ($numericTs > 1000000000000) {
                        $numericTs = (int)($numericTs / 1000);
                    }
                    if ($numericTs > 0) {
                        $messageDate = Carbon::createFromTimestamp($numericTs);
                    }
                } elseif (is_string($rawTs)) {
                    try {
                        $messageDate = Carbon::parse($rawTs);
                    } catch (\Exception $e) {
                        $messageDate = now();
                    }
                }
            }

            $message = $this->whatsAppService->createMessage(
                $chat,
                $extractionResult['raw_text'] ?: 'Imported message',
                $extractionResult['image_path'] ? 'image' : 'text',
                $extractionResult['image_path'] ?? null,
                $extractionResult['external_message_id'] ?? null,
                $messageDate
            );

            $order = Order::create([
                'user_id' => $userId,
                'whatsapp_chat_id' => $chat->id,
                'whatsapp_message_id' => $message->id,
                'order_id' => strtoupper($parsed['order_id']),
                'customer_name' => $parsed['customer_name'],
                'phone_number' => $phone,
                'status' => 'NEW',
                'packing_status' => 'NOT_PACKED',
                'source' => $extractionResult['source'] ?? 'whatsapp_manual',
                'extraction_confidence' => $parsed['confidence'],
                'created_at' => $messageDate,
                'updated_at' => $messageDate,
            ]);

            // Automatically check and link any existing unmatched shipping label for this Order ID!
            $unmatchedLabel = OrderLabel::where('detected_order_id', $order->order_id)
                ->where(function ($q) {
                    $q->whereNull('order_id')->orWhere('status', '!=', 'LABEL_MATCHED');
                })
                ->first();

            if ($unmatchedLabel) {
                $unmatchedLabel->update([
                    'order_id' => $order->id,
                    'status' => 'LABEL_MATCHED',
                    'confidence' => 100.00,
                    'processed_at' => now(),
                ]);

                $order->update([
                    'packing_status' => 'READY',
                ]);
            }

            return $order;
        });

        // Trigger Meesho Auto-Processing if order ID is from Meesho
        if (config('services.meesho.auto_accept', true) && $this->meeshoBotService->isMeeshoOrderId($order->order_id)) {
            try {
                $this->meeshoBotService->processOrder(
                    $order,
                    config('services.meesho.auto_accept', true),
                    config('services.meesho.auto_download_label', true)
                );
            } catch (\Exception $e) {
                \Log::warning("Meesho auto-process error for order {$order->order_id}: " . $e->getMessage());
            }
        }

        return $order;
    }

    /**
     * Process Standalone WhatsApp Chat Export (.txt file without media).
     */
    public function processWhatsAppTxtExport(string $chatContent, ?int $userId = null): array
    {
        $userId = $userId ?: auth()->id();
        $lines = explode("\n", $chatContent);

        $results = [
            'processed_messages' => 0,
            'orders_created' => 0,
            'duplicates_count' => 0,
            'orders' => [],
        ];

        $messages = [];
        $currentMessage = null;

        foreach ($lines as $line) {
            $line = preg_replace('/[\x{200B}-\x{200D}\x{200E}\x{200F}\x{202F}\x{FEFF}]/u', '', $line);
            $line = trim($line);
            if (empty($line)) continue;

            $matched = false;

            // Pattern A: [24/08/2026, 11:30:00 AM] Sender Name: Message content
            if (preg_match('/^\[([^\]]+)\]\s*([^:]+):\s*(.*)$/u', $line, $m)) {
                $matched = true;
                $timestamp = trim($m[1]);
                $sender = trim(ltrim(trim($m[2]), '~@ '));
                $content = trim($m[3]);
            }
            // Pattern B: 24/08/26, 11:30 AM - Sender Name: Message content
            elseif (preg_match('/^(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4}[^\:]*?)\s*\-\s*([^:]+):\s*(.*)$/i', $line, $m)) {
                $matched = true;
                $timestamp = trim($m[1]);
                $sender = trim(ltrim(trim($m[2]), '~@ '));
                $content = trim($m[3]);
            }

            if ($matched) {
                if ($currentMessage) {
                    $messages[] = $currentMessage;
                }
                $currentMessage = [
                    'timestamp' => $timestamp,
                    'sender' => $sender,
                    'text' => $content,
                ];
            } else {
                if ($currentMessage) {
                    $currentMessage['text'] .= "\n" . $line;
                } else {
                    $currentMessage = [
                        'timestamp' => now()->toDateTimeString(),
                        'sender' => 'Customer',
                        'text' => $line,
                    ];
                }
            }
        }
        if ($currentMessage) {
            $messages[] = $currentMessage;
        }

        $chatCustomerName = 'Customer';
        $chatPhoneNumber = null;

        foreach ($messages as $msg) {
            if (!empty($msg['sender']) && !in_array(strtolower($msg['sender']), ['system', 'vishvkarma gifts'])) {
                $chatCustomerName = $msg['sender'];
            }
            $p = $this->parseMessageText($msg['text']);
            if ($p['phone_number']) {
                $chatPhoneNumber = $p['phone_number'];
            }
        }

        foreach ($messages as $msg) {
            $results['processed_messages']++;
            $parsed = $this->parseMessageText($msg['text']);

            if ($parsed['order_id']) {
                $customerName = (!empty($msg['sender']) && !in_array(strtolower($msg['sender']), ['system', 'vishvkarma gifts'])) ? $msg['sender'] : $chatCustomerName;

                $extractionResult = [
                    'parsed' => array_merge($parsed, [
                        'customer_name' => $customerName,
                        'phone_number' => $parsed['phone_number'] ?: $chatPhoneNumber,
                    ]),
                    'user_id' => $userId,
                    'is_duplicate' => Order::where('order_id', strtoupper($parsed['order_id']))->exists(),
                    'existing_order' => Order::where('order_id', strtoupper($parsed['order_id']))->first(),
                    'raw_text' => $msg['text'],
                    'image_path' => null,
                ];

                if ($extractionResult['is_duplicate']) {
                    $results['duplicates_count']++;
                    $results['orders'][] = [
                        'order' => $extractionResult['existing_order'],
                        'is_duplicate' => true,
                    ];
                } else {
                    $order = $this->createOrderFromExtraction($extractionResult);
                    $results['orders_created']++;
                    $results['orders'][] = [
                        'order' => $order->load(['chat', 'message']),
                        'is_duplicate' => false,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Process WhatsApp Chat Export with Media (.zip archive).
     */
    public function processWhatsAppZipExport(UploadedFile $zipFile, ?int $userId = null): array
    {
        $userId = $userId ?: auth()->id();
        $zip = new ZipArchive();
        $results = [
            'processed_messages' => 0,
            'orders_created' => 0,
            'duplicates_count' => 0,
            'orders' => [],
        ];

        if ($zip->open($zipFile->getRealPath()) !== true) {
            return array_merge($results, ['error' => 'Could not open ZIP archive.']);
        }

        $folderName = 'whatsapp_exports/' . uniqid('export_');
        $extractPath = Storage::disk('public')->path($folderName);
        $zip->extractTo($extractPath);
        $zip->close();

        // Locate chat text file inside extracted folder
        $allFiles = Storage::disk('public')->allFiles($folderName);
        $chatFile = null;

        foreach ($allFiles as $file) {
            $filename = basename($file);
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'txt') {
                $chatFile = $file;
                break;
            }
        }

        if (!$chatFile) {
            return array_merge($results, ['error' => 'No WhatsApp chat text file found in ZIP archive.']);
        }

        $chatContent = Storage::disk('public')->get($chatFile);
        $lines = explode("\n", $chatContent);

        $messages = [];
        $currentMessage = null;

        foreach ($lines as $line) {
            $line = preg_replace('/[\x{200B}-\x{200D}\x{200E}\x{200F}\x{202F}\x{FEFF}]/u', '', $line);
            $line = trim($line);
            if (empty($line)) continue;

            $matched = false;

            if (preg_match('/^\[([^\]]+)\]\s*([^:]+):\s*(.*)$/u', $line, $m)) {
                $matched = true;
                $timestamp = trim($m[1]);
                $sender = trim(ltrim(trim($m[2]), '~@ '));
                $content = trim($m[3]);
            } elseif (preg_match('/^(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4}[^\:]*?)\s*\-\s*([^:]+):\s*(.*)$/i', $line, $m)) {
                $matched = true;
                $timestamp = trim($m[1]);
                $sender = trim(ltrim(trim($m[2]), '~@ '));
                $content = trim($m[3]);
            }

            if ($matched) {
                if ($currentMessage) {
                    $messages[] = $currentMessage;
                }
                $currentMessage = [
                    'timestamp' => $timestamp,
                    'sender' => $sender,
                    'text' => $content,
                    'attached_media' => null,
                ];
            } else {
                if ($currentMessage) {
                    $currentMessage['text'] .= "\n" . $line;
                } else {
                    $currentMessage = [
                        'timestamp' => now()->toDateTimeString(),
                        'sender' => 'Customer',
                        'text' => $line,
                        'attached_media' => null,
                    ];
                }
            }
        }
        if ($currentMessage) {
            $messages[] = $currentMessage;
        }

        foreach ($messages as &$msg) {
            $results['processed_messages']++;
            if (preg_match('/([a-zA-Z0-9_\-]+\.(?:jpg|jpeg|png|webp|pdf))/i', $msg['text'], $mediaMatch)) {
                $mediaName = $mediaMatch[1];
                foreach ($allFiles as $extractedFile) {
                    if (basename($extractedFile) === $mediaName) {
                        $msg['attached_media'] = $extractedFile;
                        break;
                    }
                }
            }
        }
        unset($msg);

        $chatCustomerName = 'Customer';
        $chatPhoneNumber = null;

        foreach ($messages as $msg) {
            if (!empty($msg['sender']) && !in_array(strtolower($msg['sender']), ['system', 'vishvkarma gifts'])) {
                $chatCustomerName = $msg['sender'];
            }
            $p = $this->parseMessageText($msg['text']);
            if ($p['phone_number']) {
                $chatPhoneNumber = $p['phone_number'];
            }
        }

        foreach ($messages as $msg) {
            $parsed = $this->parseMessageText($msg['text']);

            if (!$parsed['order_id'] && $msg['attached_media']) {
                $fullMediaPath = Storage::disk('public')->path($msg['attached_media']);
                $ocrText = $this->ocrService->extractTextFromImage($fullMediaPath);
                if ($ocrText) {
                    $parsed = $this->parseMessageText($ocrText);
                }
            }

            if ($parsed['order_id']) {
                $customerName = (!empty($msg['sender']) && !in_array(strtolower($msg['sender']), ['system', 'vishvkarma gifts'])) ? $msg['sender'] : $chatCustomerName;

                $extractionResult = [
                    'parsed' => array_merge($parsed, [
                        'customer_name' => $customerName,
                        'phone_number' => $parsed['phone_number'] ?: $chatPhoneNumber,
                    ]),
                    'user_id' => $userId,
                    'is_duplicate' => Order::where('order_id', strtoupper($parsed['order_id']))->exists(),
                    'existing_order' => Order::where('order_id', strtoupper($parsed['order_id']))->first(),
                    'raw_text' => $msg['text'],
                    'image_path' => $msg['attached_media'] ?? null,
                ];

                if ($extractionResult['is_duplicate']) {
                    $results['duplicates_count']++;
                    $results['orders'][] = [
                        'order' => $extractionResult['existing_order'],
                        'is_duplicate' => true,
                    ];
                } else {
                    $order = $this->createOrderFromExtraction($extractionResult);
                    $results['orders_created']++;
                    $results['orders'][] = [
                        'order' => $order->load(['chat', 'message']),
                        'is_duplicate' => false,
                    ];
                }
            }
        }

        return $results;
    }
}

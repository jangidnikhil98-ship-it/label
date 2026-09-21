<?php

namespace App\Services;

use App\Models\WhatsAppChat;
use App\Models\WhatsAppMessage;

class WhatsAppService
{
    /**
     * Normalize Indian phone numbers to 10 digits standard.
     */
    public function normalizePhoneNumber(?string $phone): string
    {
        if (!$phone) {
            return '';
        }

        // Remove all non-numeric characters
        $digits = preg_replace('/\D/', '', $phone);

        // Handle Indian country codes (+91, 91) and leading zeros
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Find or create chat by phone number and customer name scoped to current user.
     */
    public function findOrCreateChat(string $phone, ?string $customerName = null, ?int $userId = null): WhatsAppChat
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        $userId = $userId ?: auth()->id();

        $chat = WhatsAppChat::firstOrCreate(
            [
                'user_id' => $userId,
                'phone_number' => $normalizedPhone,
            ],
            [
                'customer_name' => $customerName ?: 'Customer ' . substr($normalizedPhone, -4),
            ]
        );

        if ($customerName && (empty($chat->customer_name) || str_starts_with($chat->customer_name, 'Customer '))) {
            $chat->update(['customer_name' => $customerName]);
        }

        return $chat;
    }

    /**
     * Record a WhatsApp message.
     */
    public function createMessage(
        WhatsAppChat $chat,
        string $text,
        string $type = 'text',
        ?string $mediaPath = null,
        ?string $externalMessageId = null,
        mixed $receivedAt = null
    ): WhatsAppMessage {
        return WhatsAppMessage::create([
            'whatsapp_chat_id' => $chat->id,
            'external_message_id' => $externalMessageId,
            'message_type' => $type,
            'message_text' => $text,
            'media_path' => $mediaPath,
            'received_at' => $receivedAt ?: now(),
        ]);
    }
}

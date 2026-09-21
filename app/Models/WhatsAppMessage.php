<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'whatsapp_chat_id',
        'external_message_id',
        'message_type',
        'message_text',
        'media_path',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(WhatsAppChat::class, 'whatsapp_chat_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'whatsapp_message_id');
    }
}

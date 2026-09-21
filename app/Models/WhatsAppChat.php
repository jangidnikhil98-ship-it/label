<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppChat extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_chats';

    protected $fillable = [
        'user_id',
        'whatsapp_account_id',
        'external_chat_id',
        'customer_name',
        'phone_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'whatsapp_chat_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'whatsapp_chat_id');
    }
}

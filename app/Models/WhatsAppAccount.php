<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppAccount extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'user_id',
        'account_name',
        'phone_number',
        'provider',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $table = $this->belongsTo(User::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(WhatsAppChat::class, 'whatsapp_account_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'whatsapp_chat_id',
        'whatsapp_message_id',
        'order_id',
        'customer_name',
        'phone_number',
        'status',
        'packing_status',
        'source',
        'extraction_confidence',
        'verified_at',
        'accepted_at',
        'packed_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'extraction_confidence' => 'float',
        'verified_at' => 'datetime',
        'accepted_at' => 'datetime',
        'packed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(WhatsAppChat::class, 'whatsapp_chat_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }

    public function label(): HasOne
    {
        return $this->hasOne(OrderLabel::class, 'order_id');
    }

    public function getLabelStatusAttribute(): string
    {
        return $this->label ? $this->label->status : 'LABEL_PENDING';
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', strtoupper($status));
    }

    public function scopePackingStatus($query, string $packingStatus)
    {
        return $query->where('packing_status', strtoupper($packingStatus));
    }
}

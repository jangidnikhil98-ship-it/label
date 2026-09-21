<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLabel extends Model
{
    use HasFactory;

    protected $table = 'order_labels';

    protected $fillable = [
        'user_id',
        'order_id',
        'file_path',
        'file_name',
        'file_type',
        'detected_order_id',
        'confidence',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

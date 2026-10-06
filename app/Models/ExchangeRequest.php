<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRequest extends Model
{
    public const STATUSES = ['pending', 'processing', 'completed', 'rejected'];

    protected $fillable = [
        'user_id',
        'usdt_amount',
        'exchange_rate',
        'inr_amount',
        'status',
        'transaction_reference',
        'admin_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

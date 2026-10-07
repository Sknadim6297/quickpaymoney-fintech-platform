<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ExchangeRequest extends Model
{
    public const STATUSES = ['pending', 'processing', 'completed', 'rejected'];

    protected $fillable = [
        'user_id',
        'request_reference',
        'submission_key',
        'rate_plan_name',
        'usdt_amount',
        'exchange_rate',
        'inr_amount',
        'status',
        'transaction_reference',
        'admin_notes',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $exchange): void {
            foreach ([
                'user_id',
                'request_reference',
                'submission_key',
                'rate_plan_name',
                'usdt_amount',
                'exchange_rate',
                'inr_amount',
            ] as $attribute) {
                if ($exchange->isDirty($attribute)) {
                    throw new LogicException('Exchange amount and rate snapshots are immutable.');
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

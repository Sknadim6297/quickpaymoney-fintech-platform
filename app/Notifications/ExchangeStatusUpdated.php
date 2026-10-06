<?php

namespace App\Notifications;

use App\Models\ExchangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExchangeStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(private readonly ExchangeRequest $exchangeRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Your exchange request #'.$this->exchangeRequest->id.' is now '.$this->exchangeRequest->status.'.',
            'exchange_request_id' => $this->exchangeRequest->id,
            'status' => $this->exchangeRequest->status,
        ];
    }
}

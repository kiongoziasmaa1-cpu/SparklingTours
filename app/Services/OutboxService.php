<?php

namespace App\Services;

use App\Jobs\SendOutboxMessage;
use App\Models\OutboxMessage;
use Illuminate\Support\Str;

class OutboxService
{
    public function create(string $recipient, string $subject, array $payload): OutboxMessage
    {
        return OutboxMessage::create(['public_id' => (string) Str::uuid(), 'recipient' => $recipient, 'subject' => $subject, 'payload' => $payload]);
    }

    public function dispatchPending(): void
    {
        foreach (OutboxMessage::whereIn('status', ['pending', 'failed'])->where('attempts', '<', 8)->orderBy('id')->limit(50)->get() as $message) {
            try {
                SendOutboxMessage::dispatch($message->id)->afterCommit();
            } catch (\Throwable $e) {
                if ($message->fresh()?->status !== 'failed') {
                    $message->update(['status' => 'pending', 'last_error' => 'Queue unavailable. Delivery will be retried.']);
                } report(new \RuntimeException('Notification queue unavailable.'));
            }
        }
    }
}

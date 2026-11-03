<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\OutboxMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendOutboxMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public int $messageId) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $message = DB::transaction(function () {
            $m = OutboxMessage::lockForUpdate()->find($this->messageId);
            if (! $m || $m->status === 'sent' || ($m->status === 'processing' && $m->processing_at?->greaterThan(now()->subMinutes(10)))) {
                return null;
            }
            $m->update(['status' => 'processing', 'processing_at' => now(), 'attempts' => $m->attempts + 1]);

            return $m;
        });
        if (! $message) {
            return;
        }
        try {
            $p = $message->payload;
            $booking = isset($p['booking_id']) ? Booking::with(['items', 'accommodation', 'transport'])->find($p['booking_id']) : null;
            Mail::send('mail.notification', ['payload' => $p, 'booking' => $booking, 'subject' => $message->subject], function ($mail) use ($message) {
                $mail->to($message->recipient)->subject($message->subject);
                $mail->getSymfonyMessage()->getHeaders()->addIdHeader('X-Booking-Message-ID', $message->public_id.'@notifications.local');
            });
            $message->update(['status' => 'sent', 'sent_at' => now(), 'processing_at' => null, 'last_error' => null]);
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'processing_at' => null, 'last_error' => 'Mail delivery failed. Check mail transport and retry.']);
            throw new \RuntimeException('Mail delivery failed; saved request retained.', 0, $e);
        }
    }
}

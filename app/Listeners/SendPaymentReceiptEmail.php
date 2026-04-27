<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Notifications\PaymentReceiptNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentReceiptEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;
        $payment->loadMissing(['user', 'course']);

        $payment->user->notify(new PaymentReceiptNotification($payment));
    }
}

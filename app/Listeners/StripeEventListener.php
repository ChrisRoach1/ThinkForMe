<?php

namespace App\Listeners;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle received Stripe webhooks.
     */
    public function handle(WebhookReceived $event): void
    {

        Log::debug('userID: '. $event->payload['data']['object']['metadata']['user_id']);
        Log::debug('payload type: '. $event->payload['type']);
        if($event->payload['type'] === 'payment_intent.succeeded') {
            $userID = $event->payload['data']['object']['metadata']['user_id'] ?? null;

            if($userID === null) {
                return;
            }

            Purchase::create([
               'user_id' => $userID,
                'stripe_purchase_id' => $event->payload['data']['object']['id'],
                'product' => $event->payload['data']['object']['metadata']['product_key'] ?? "",
            ]);

            User::findOrFail($userID)->increment('credits', 65);
        }
    }
}

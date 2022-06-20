<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Sumra\SDK\Facades\PubSub;

class PartnerRegisterRequestListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param array $event
     *
     * @return void
     */
    public function handle(array $data): void
    {
        try {
            $user = new User();
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->email = $data['last_name'] ?? null;
            $user->phone = $data['mobile'] ?? null;

            $user->save();

            PubSub::publish('partnerRegisterResponse', $user, config('settings.pubsub_receiver.gmet_partners'));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }
}

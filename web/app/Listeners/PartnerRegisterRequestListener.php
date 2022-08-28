<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Sumra\SDK\Facades\PubSub;

class PartnerRegisterRequestListener
{
    /**
     * Handle the event.
     *
     * @param array $data
     *
     *
     *
     * @return void
     */
    public function handle(array $data): void
    {
        try {
            $user = new User();
            $user->fill($data);
            $user->save();

            PubSub::publish('partnerRegisterResponse', $user, config('pubsub.queue.g_met'));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }
}

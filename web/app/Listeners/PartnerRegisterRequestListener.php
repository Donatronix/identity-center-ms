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
     * @param array $event
     *
     * @return void
     */
    public function handle(array $data): void
    {
        try {
            $user = new User();
            $user->id = $data['id'];
            $user->username = $data['first_name'].".".$data['last_name'];
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->email = $data['email'] ?? null;
            $user->phone = $data['mobile'] ?? null;

            $user->save();

            PubSub::publish('partnerRegisterResponse', $user, config('pubsub.queue.g_met'));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }
}

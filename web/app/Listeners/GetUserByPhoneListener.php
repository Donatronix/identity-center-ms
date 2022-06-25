<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Sumra\SDK\Facades\PubSub;

class GetUserByPhoneListener
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
            $user = User::where("phone", $data["phone"])->first();

            Log::info($user);


        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }
}

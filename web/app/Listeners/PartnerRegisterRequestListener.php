<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class PartnerRegisterRequestListener
{
    /**
     * Handle the event.
     *
     * @param array $data
     * @return void
     */
    public function handle(array $data): void
    {
        try {
            $user = new User();
            $user->fill([
                'id' => $data['id'],
                'username' => strtolower($data['first_name'] . "." . $data['last_name']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['mobile'] ?? null,
                'status' => 1,
                'is_agreement' => true,
            ]);
            $user->save();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }
}

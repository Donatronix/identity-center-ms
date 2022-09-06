<?php

namespace App\Listeners;

use App\Models\Role;
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
                'username' => $data['username'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['mobile'] ?? null,
                'status' => 1,
                'is_agreement' => true,
            ]);
            $user->save();

            // Update role
            $role = Role::firstOrCreate([
                'name' => Role::ROLE_INFLUENCER
            ]);
            $user->roles()->sync($role->id);

        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }
}

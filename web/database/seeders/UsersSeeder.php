<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(config('settings.default_users_ids') as $uuid){
            User::factory()->create([
                'id' => $uuid
            ]);
        }
    }
}

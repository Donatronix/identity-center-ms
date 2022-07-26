<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class ClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $lists = [
            [
                'name' => 'Identity Centre API Microservice Personal Access Client',
                'secret' => Str::random(40),
                'provider' => 'users',
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 1,
                'revoked' => 0
            ],
            [
                'name' => 'Identity Centre API Microservice Personal Access Client',
                'secret' => Str::random(40),
                'provider' => 'users',
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 1,
                'revoked' => 0
            ]
        ];

        foreach ($lists as $list) {
            $client = Client::create($list);

            DB::table('oauth_personal_access_clients')->insert([
                'client_id' => $client->id
            ]);
        }
    }
}

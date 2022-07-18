<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            //ClientsSeeder::class,
            BotsTableSeeder::class,
            RoleSeeder::class,
        ]);

        // Seeds for local and staging
        if (App::environment(['local', 'staging'])) {
            $this->call([
                UsersTableSeeder::class,
                KYCsTableSeeder::class
            ]);
        }

        // Seeds for production
        if (App::environment('production')) {
//            $this->call([
//            ]);
        }
    }
}

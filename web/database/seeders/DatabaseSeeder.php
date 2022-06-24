<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            //UsersTableSeeder::class,
            ClientsSeeder::class,
            RoleSeeder::class,
            BotsTableSeeder::class,
        ]);
    }
}

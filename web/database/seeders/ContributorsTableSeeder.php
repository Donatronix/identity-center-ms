<?php

namespace Database\Seeders;

use App\Models\Contributor;
use Illuminate\Database\Seeder;

class ContributorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run(): void
    {
        // Generate default contributors
        foreach(config('settings.default_users_ids') as $uuid){
            Contributor::factory()->create([
                'id' => $uuid
            ]);
        }
    }
}

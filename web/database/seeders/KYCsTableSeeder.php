<?php

namespace Database\Seeders;

use App\Models\KYC;
use Illuminate\Database\Seeder;

class KYCsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        KYC::factory()->count(10)->create();
    }
}

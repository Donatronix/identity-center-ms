<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
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
        // Seeds for all
        $this->call([
            ClientsSeeder::class,
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

        // Get super admin role
        $role = Role::firstOrCreate([
            'name' => Role::ROLE_SUPER_ADMIN
        ]);

        // Add Super ADMIN
        $user = User::create([
            'id' => '856c4ddc-64de-463b-8656-94b70deb94a5',
            'first_name' => 'Mr',
            'last_name' => 'Michael',
            'username' => 'GLOBALONE',
            'phone' => '447788878354',
            'email' => 'thesolegroup@gmail.com',
            'address_zip' => 'W6 7AE',
            'address_country' => 'GB',
            'address_city' => 'London',
            'is_agreement' => '1',
            'status' => '1',
            'is_kyc_verified' => '1',
        ]);
        $user->roles()->sync($role->id);

        // Add ADMIN TESTER
        $user = User::create([
            'id' => '4207cdf9-4588-43bf-9c4f-7c5056c14b4d',
            'first_name' => 'Victoria',
            'last_name' => 'Test',
            'username' => 'VICTORIA2108',
            'phone' => '447741593826',
            'email' => 'innovate.hq@yahoo.com',
            'address_zip' => 'W6 7AE',
            'address_country' => 'GB',
            'address_city' => 'London',
            'address_line1' => '12 Hammersmith Glove',
            'is_agreement' => '1',
            'status' => '1',
            'is_kyc_verified' => '1',
        ]);
        $user->roles()->sync($role->id);

        // ADD MAIN DEVELOPER
        $user = User::create([
            'id' => '9bac99e4-95db-412e-a36b-cbc891b553b8',
            'first_name' => 'Ihor',
            'last_name' => 'Porokhnenko',
            'username' => 'dhanaprofit',
            'phone' => '380971819100',
            'email' => 'ihor.porokhnenko@gmail.com',
            'address_zip' => '04201',
            'address_country' => 'UA',
            'address_city' => 'Kiev',
            'is_agreement' => '1',
            'status' => '1',
            'is_kyc_verified' => '1',
        ]);
        $user->roles()->sync($role->id);
    }
}

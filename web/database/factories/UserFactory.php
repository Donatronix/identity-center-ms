<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            /**
             * User common data
             */
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'username' => $this->faker->userName(),
            'phone' => Str::after($this->faker->e164PhoneNumber(), '+'),
            'email' => $this->faker->unique()->safeEmail(),
            'gender' => '',
//            'email_verified_at' => now(),
//            'birthday' => '',
            'birthday' => $this->faker->date(),
            'password' => Hash::make($this->faker->password(8)),
//            'remember_token' => Str::random(10),
            'status' => Arr::random(User::$statuses),

            /**
             * User address
             */
            'address_country' => $this->faker->countryCode(),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->secondaryAddress(),
            'address_city' => $this->faker->city(),
            'address_zip' => $this->faker->postcode(),

            'status' => User::STATUS_INACTIVE,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}

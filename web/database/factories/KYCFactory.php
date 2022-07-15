<?php

namespace Database\Factories;

use App\Models\KYC;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class KYCFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = KYC::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
    	return [
            /**
             * User document
             */
            'id_number' => '',
            'document_number' => '',
            'document_country' => $this->faker->countryCode(),
            'document_type' => Arr::random(KYC::$document_types),
            'document_front' => '',
            'document_back' => '',
            'portrait' => '',
            'user_id'  => User::all()->random(),
            'status' => Arr::random(KYC::$statuses),
    	];
    }
}

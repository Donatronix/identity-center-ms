<?php

namespace Database\Factories;

use App\Models\Contributor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class ContributorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Contributor::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);

        return [
            /**
             * Contributor common data
             */
            'first_name' => $this->faker->lastName(),
            'last_name' => $this->faker->lastName(),
            'gender' => '',
            'date_birthday' => $this->faker->date(),
            'email' => $this->faker->unique()->safeEmail,
            'id_number' => '',

            /**
             * Contributor address
             */
            'address_country' => $this->faker->countryCode(),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->secondaryAddress(),
            'address_city' => $this->faker->city(),
            'address_zip' => $this->faker->postcode(),

            /**
             * Contributor document
             */
            'document_number' => '',
            'document_country' => $this->faker->countryCode(),
            'document_type' => Arr::random(Contributor::$document_types),
            'document_file' => '',

            'status' => Contributor::STATUS_STEP_1,
            'is_agreement' => $this->faker->boolean(),
        ];
    }
}

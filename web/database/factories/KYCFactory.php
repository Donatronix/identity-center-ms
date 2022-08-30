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
            'id_doctype' => Arr::random(KYC::$document_types),
            'address_verify_doctype' => '',

            'id_document' => '',
            'address_verify_document' => '',
            'portrait' => '',

            'user_id'  => User::all()->random(),
            'status' => Arr::random(KYC::$statuses),
    	];
    }
}

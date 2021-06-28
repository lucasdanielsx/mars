<?php

namespace Database\Factories;

use App\Helpers\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

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
     * @throws \Exception
     */
    public function definition()
    {
        return [
            'id' => Uuid::uuid4(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'document_value' => (string) random_int(11111111111, 9999999999999),
            'type' => UserType::CUSTOMER,
        ];
    }
}

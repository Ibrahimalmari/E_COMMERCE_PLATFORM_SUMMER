<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sellerman>
 */
class SellermanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'personalNumber' =>$this->faker->unique()->number(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'status' => $this->faker->status(),
            'gender' => $this->faker->gender(),
            'personal_identity_photo' => $this->faker->photo(),
            'birthday' => $this->faker->birthday(),  
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }
}

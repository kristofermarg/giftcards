<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Services\GiftcardCode;

class GiftcardFactory extends Factory
{
    public function definition(): array
    {
        $amount = $this->faker->numberBetween(1000, 10000); 
        return [
            'public_id'      => (string) Str::uuid(),
            'code'           => GiftcardCode::make(),
            'initial_amount' => $amount,
            'balance'        => $amount,
            'currency'       => 'ISK',
            'status'         => 'active',
            'expires_at'     => now()->addMonths($this->faker->numberBetween(3,12)),
            'meta'           => [
                'owner_name'  => $this->faker->name(),
                'owner_email' => $this->faker->safeEmail(),
            ],
        ];
    }
}

<?php

namespace Database\Factories;

use App\Services\GiftcardCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class GiftcardFactory extends Factory
{
    public function definition(): array
    {
        $amount = random_int(1000, 10000);
        $expiryMonths = random_int(3, 12);

        return [
            'code' => GiftcardCode::make(),
            'initial_amount_cents' => $amount,
            'balance_cents' => $amount,
            'currency' => 'ISK',
            'status' => 'active',
            'expires_at' => now()->addMonths($expiryMonths),
            'meta' => [
                'owner_name' => 'Giftcard Owner #' . random_int(1000, 9999),
                'owner_email' => 'owner' . random_int(1000, 9999) . '@example.com',
            ],
        ];
    }
}

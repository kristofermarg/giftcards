<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Services\GiftcardCode;

class GiftcardFactory extends Factory
{
    public function definition(): array
    {
        $amount = random_int(1000, 10000);
        $expiryMonths = random_int(3, 12);

        return [
            'public_id'      => (string) Str::uuid(),
            'code'           => GiftcardCode::make(),
            'initial_amount' => $amount,
            'balance'        => $amount,
            'currency'       => 'ISK',
            'status'         => 'active',
            'expires_at'     => now()->addMonths($expiryMonths),
            'meta'           => [
                'owner_name'  => 'Giftcard Owner #' . random_int(1000, 9999),
                'owner_email' => 'owner' . random_int(1000, 9999) . '@example.com',
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Giftcard;
use Illuminate\Database\Seeder;

class GiftcardSeeder extends Seeder
{
    public function run(): void
    {
        // Major ISK amounts (what a human sees). We'll store *100 (minor units) in DB.
        $majors = [10000, 15000, 25000, 5000];

        foreach ($majors as $i => $major) {
            $minor = $major * 100;

            $gc = Giftcard::factory()->state([
                'initial_amount_cents' => 0,
                'balance_cents' => 0,
                'currency' => 'ISK',
                'status' => 'active',
                'expires_at' => now()->addMonths(6 + $i),
            ])->create();

            $gc->issue($minor, $this->orderRef(), ['reason' => 'seed_initial_load']);

            $debitCount = random_int(0, 3);
            for ($n = 0; $n < $debitCount; $n++) {
                $candidate = (int) round($minor * (random_int(10, 40) / 100));
                $debitAmt = max(1000, min($candidate, $gc->balance));

                if ($debitAmt <= 0 || $debitAmt > $gc->balance) {
                    break;
                }

                try {
                    $gc->redeem($debitAmt, $this->orderRef(), ['reason' => 'seed_random_redeem']);
                } catch (\Throwable $e) {
                    break;
                }
            }
        }
    }

    private function orderRef(): string
    {
        return 'WooCommerce order #' . random_int(100000, 999999);
    }
}

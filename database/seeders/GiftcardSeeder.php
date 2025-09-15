<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Giftcard;

class GiftcardSeeder extends Seeder
{
    public function run(): void
    {
        // Major ISK amounts (what a human sees). We'll store *100 (minor units) in DB.
        $majors = [10000, 15000, 25000, 5000]; // ISK

        foreach ($majors as $i => $major) {
            $minor = $major * 100; // store as minor units to fit your current /100 display

            // Create the card (override currency to ISK)
            $gc = Giftcard::factory()->state([
                'initial_amount' => $minor,
                'balance'        => $minor,
                'currency'       => 'ISK',
                'status'         => 'active',
                'expires_at'     => now()->addMonths(6 + $i), // stagger expiries
            ])->create();

            // Initial load transaction (ledger entry only; balance already set above)
            $gc->transactions()->create([
                'type'      => 'credit',
                'amount'    => $minor,
                'reference' => $this->orderRef(),
            ]);

            // Add 0–3 random debits without overdrawing
            $debitCount = random_int(0, 3);
            for ($n = 0; $n < $debitCount; $n++) {
                // pick a debit between 10% and 40% of initial, but not more than current balance
                $candidate = (int) round($minor * (random_int(10, 40) / 100));
                $debitAmt  = max(1000, min($candidate, $gc->balance)); // >= 1000 minor units (10.00)

                if ($debitAmt <= 0 || $debitAmt > $gc->balance) break;

                // Use model method to correctly reduce balance + create txn
                try {
                    $gc->debit($debitAmt, $this->orderRef());
                } catch (\Throwable $e) {
                    break; // stop if anything odd happens
                }
            }
        }
    }

    private function orderRef(): string
    {
        return 'WooCommerce order #' . random_int(100000, 999999);
    }
}

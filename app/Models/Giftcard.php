<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Giftcard extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'gift_cards';

    protected $fillable = [
        'code',
        'initial_amount_cents',
        'balance_cents',
        'currency',
        'status',
        'expires_at',
        'meta',
        'passkit_program_id',
        'passkit_member_id',
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'date',
        'initial_amount_cents' => 'int',
        'balance_cents' => 'int',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftcardTransaction::class, 'gift_card_id')->latest('created_at');
    }

    public function formatAmount(int $minor): string
    {
        if ($this->currency === 'ISK') {
            return number_format($minor / 100, 0) . ' ISK';
        }

        return number_format($minor / 100, 2) . ' ' . $this->currency;
    }

    public function issue(int $amountMinor, ?string $reference = null, array $details = [], ?int $woocommerceOrderId = null, ?string $idempotencyKey = null): GiftcardTransaction
    {
        return $this->applyTransaction('issue', $amountMinor, $reference, $details, null, $woocommerceOrderId, $idempotencyKey);
    }

    public function redeem(int $amountMinor, ?string $reference = null, array $details = [], ?int $woocommerceOrderId = null, ?string $idempotencyKey = null): GiftcardTransaction
    {
        return $this->applyTransaction('redeem', $amountMinor, $reference, $details, null, $woocommerceOrderId, $idempotencyKey);
    }

    public function refund(int $amountMinor, ?string $reference = null, array $details = [], ?int $woocommerceOrderId = null, ?string $idempotencyKey = null): GiftcardTransaction
    {
        return $this->applyTransaction('refund', $amountMinor, $reference, $details, null, $woocommerceOrderId, $idempotencyKey);
    }

    public function adjust(int $deltaMinor, ?string $reference = null, array $details = [], ?int $woocommerceOrderId = null, ?string $idempotencyKey = null): GiftcardTransaction
    {
        if ($deltaMinor === 0) {
            throw new InvalidArgumentException('Adjustment delta must be non-zero.');
        }

        $amount = abs($deltaMinor);
        $details = array_merge([
            'delta_minor' => $deltaMinor,
            'delta_major' => $this->minorToMajor($deltaMinor),
        ], $details);

        return $this->applyTransaction('adjust', $amount, $reference, $details, $deltaMinor, $woocommerceOrderId, $idempotencyKey);
    }

    protected function applyTransaction(
        string $direction,
        int $amountMinor,
        ?string $reference,
        array $details,
        ?int $deltaOverride = null,
        ?int $woocommerceOrderId = null,
        ?string $idempotencyKey = null
    ): GiftcardTransaction {
        if (!in_array($direction, ['issue','redeem','refund','adjust'], true)) {
            throw new InvalidArgumentException('Unsupported transaction direction.');
        }

        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($direction, $amountMinor, $reference, $details, $deltaOverride, $woocommerceOrderId, $idempotencyKey) {
            $before = (int) $this->balance_cents;
            $delta = $deltaOverride ?? $this->defaultDeltaFor($direction, $amountMinor);

            if ($delta < 0 && abs($delta) > $before) {
                throw new InvalidArgumentException('Amount exceeds available balance.');
            }

            $after = $before + $delta;
            if ($after < 0) {
                throw new InvalidArgumentException('Resulting balance would be negative.');
            }

            $this->balance_cents = $after;
            if ($direction === 'issue' && $this->initial_amount_cents === 0) {
                $this->initial_amount_cents = $amountMinor;
            }
            $this->save();

            return $this->transactions()->create([
                'direction' => $direction,
                'amount_cents' => $amountMinor,
                'balance_before_cents' => $before,
                'balance_after_cents' => $after,
                'reference' => $reference,
                'woocommerce_order_id' => $woocommerceOrderId,
                'idempotency_key' => $idempotencyKey,
                'details' => $this->buildLedgerDetails($direction, $amountMinor, $before, $after, $delta, $details),
            ]);
        });
    }

    protected function defaultDeltaFor(string $direction, int $amountMinor): int
    {
        return match ($direction) {
            'issue', 'refund' => $amountMinor,
            'redeem' => -$amountMinor,
            default => 0,
        };
    }

    protected function buildLedgerDetails(
        string $direction,
        int $amountMinor,
        int $balanceBefore,
        int $balanceAfter,
        int $deltaMinor,
        array $extra = []
    ): array {
        return array_merge([
            'direction' => $direction,
            'currency' => $this->currency,
            'amount_minor' => $amountMinor,
            'amount_major' => $this->minorToMajor($amountMinor),
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'delta_minor' => $deltaMinor,
            'delta_major' => $this->minorToMajor($deltaMinor),
        ], $extra);
    }

    public function getBalanceAttribute(): int
    {
        return (int) $this->balance_cents;
    }

    public function setBalanceAttribute(?int $value): void
    {
        $this->balance_cents = $value ?? 0;
    }

    public function getInitialAmountAttribute(): int
    {
        return (int) $this->initial_amount_cents;
    }

    public function setInitialAmountAttribute(?int $value): void
    {
        $this->initial_amount_cents = $value ?? 0;
    }

    protected function minorToMajor(int $amount): float
    {
        return round($amount / 100, 2);
    }
}

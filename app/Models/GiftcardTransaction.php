<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftcardTransaction extends Model
{
    protected $table = 'gift_card_transactions';

    protected $fillable = [
        'gift_card_id',
        'direction',
        'amount_cents',
        'balance_before_cents',
        'balance_after_cents',
        'woocommerce_order_id',
        'idempotency_key',
        'reference',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'amount_cents' => 'int',
        'balance_before_cents' => 'int',
        'balance_after_cents' => 'int',
        'woocommerce_order_id' => 'int',
    ];

    public function giftcard()
    {
        return $this->belongsTo(Giftcard::class, 'gift_card_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Giftcard extends Model
{
    use SoftDeletes, HasFactory;   // 👈 add HasFactory here

    protected $fillable = [
        'public_id','code','initial_amount','balance','currency',
        'status','expires_at','meta','passkit_program_id','passkit_member_id'
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'date',
    ];

    public function transactions()
    {
        return $this->hasMany(GiftcardTransaction::class)->latest();
    }

        // add this method inside the Giftcard class
    public function formatAmount(int $minor): string
    {
        if ($this->currency === 'ISK') {
            return number_format($minor / 100, 0) . ' ISK'; // 0 decimals for ISK
        }
    return number_format($minor / 100, 2) . ' ' . $this->currency; // default
}

}

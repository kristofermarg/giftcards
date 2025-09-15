<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftcardTransaction extends Model
{
    protected $fillable = ['giftcard_id','type','amount','reference','details'];
    protected $casts = ['details'=>'array'];

    public function giftcard(){ return $this->belongsTo(Giftcard::class); }
}

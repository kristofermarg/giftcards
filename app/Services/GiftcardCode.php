<?php
namespace App\Services;

use App\Models\Giftcard;
use Illuminate\Support\Str;

class GiftcardCode
{
    public static function make(int $len = 16): string
    {
        do {
            // Random uppercase alphanumeric string
            $raw = strtoupper(Str::random($len));

            // Format as groups of 4 with dashes (e.g. ABCD-EFGH-IJKL-MNOP)
            $code = implode('-', str_split(substr($raw, 0, $len), 4));
        } while (Giftcard::where('code', $code)->exists());

        return $code;
    }
}

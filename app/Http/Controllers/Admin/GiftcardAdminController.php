<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Giftcard;

class GiftcardAdminController extends Controller
{
    public function index()
    {
        $giftcards = Giftcard::where('status', 'active')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.giftcards.index', compact('giftcards'));
    }

    public function show(Giftcard $giftcard)
    {
        // Load related transactions
        $giftcard->load(['transactions' => function($q) {
            $q->orderByDesc('created_at');
        }]);

        return view('admin.giftcards.show', compact('giftcard'));
    }
}

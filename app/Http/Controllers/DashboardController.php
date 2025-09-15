<?php

namespace App\Http\Controllers;

use App\Models\Giftcard;

class DashboardController extends Controller
{
    public function index()
    {
        // fetch active giftcards
        $giftcards = Giftcard::where('status', 'active')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('dashboard', compact('giftcards'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Giftcard;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Giftcard::query()->orderByDesc('created_at');

        $status = $request->query('status');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('code', 'like', $like)
                  ->orWhere('meta->owner_name', 'like', $like)
                  ->orWhere('meta->owner_email', 'like', $like);
            });
        }

        $giftcards = $query->paginate(10)->withQueryString();

        $filters = [
            'search' => $search,
            'status' => $status ?: 'all',
        ];

        return view('dashboard', compact('giftcards', 'filters'));
    }
}

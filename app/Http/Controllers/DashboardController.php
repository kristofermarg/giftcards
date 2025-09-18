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
            $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $query->where(function ($q) use ($like, $operator) {
                $q->where('code', $operator, $like)
                  ->orWhere('meta->owner_name', $operator, $like)
                  ->orWhere('meta->owner_email', $operator, $like);
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

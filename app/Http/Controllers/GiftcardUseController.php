<?php

namespace App\Http\Controllers;

use App\Models\Giftcard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class GiftcardUseController extends Controller
{
    public function create(Request $request): View
    {
        return view('giftcards.use', [
            'result' => $request->session()->get('result'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $normalizedCode = strtoupper(trim($data['code']));

        $giftcard = Giftcard::query()
            ->whereRaw('LOWER(code) = ?', [strtolower($normalizedCode)])
            ->first();

        if (!$giftcard) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'No giftcard was found for that code.']);
        }

        if ($giftcard->status !== 'active') {
            return back()
                ->withInput()
                ->withErrors(['code' => 'This giftcard is not active.']);
        }

        if ($giftcard->expires_at && $giftcard->expires_at->isPast()) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'This giftcard has expired.']);
        }

        $amountMinor = (int) round(((float) $data['amount']) * 100);

        try {
            $giftcard->redeem(
                $amountMinor,
                $data['reference'] ?? null,
                [
                    'reason' => 'web_form_redeem',
                    'redeemed_via' => 'giftcard_form',
                    'redeemed_by_ip' => $request->ip(),
                    'requested_amount_major' => (float) $data['amount'],
                ]
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $e->getMessage()]);
        }

        $giftcard->refresh();

        return redirect()
            ->route('giftcards.use')
            ->with('result', [
                'code' => $giftcard->code,
                'amount_text' => $giftcard->formatAmount($amountMinor),
                'balance_text' => $giftcard->formatAmount($giftcard->balance),
                'currency' => $giftcard->currency,
                'reference' => $data['reference'] ?? null,
            ])
            ->with('status', 'Amount deducted successfully.');
    }
}

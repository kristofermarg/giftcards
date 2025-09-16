<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Giftcard;
use App\Services\GiftcardCode;
use App\Services\PasskitService;
use App\Models\GiftcardTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GiftcardApiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Giftcard::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $giftcards = $query->orderByDesc('created_at')->paginate($perPage);
        $giftcards->getCollection()->transform(fn (Giftcard $gc) => $this->serializeGiftcard($gc));

        return response()->json($giftcards);
    }

    public function show(Giftcard $giftcard)
    {
        return response()->json($this->serializeGiftcard($giftcard));
    }

    public function store(Request $request, PasskitService $passkit)
    {
        $data = $request->validate([
            'initial_amount' => ['required','numeric','min:0'],
            'balance'        => ['nullable','numeric','min:0'],
            'currency'       => ['nullable','string','size:3'],
            'status'         => ['nullable', Rule::in(['active','inactive','redeemed','expired'])],
            'expires_at'     => ['nullable','date'],
            'meta'           => ['nullable','array'],
            'passkit_program_id' => ['nullable','string','max:64'],
            'passkit_member_id'  => ['nullable','string','max:64'],
            // Optional WooCommerce linkage for initial transaction
            'order_id'           => ['sometimes','nullable'],
            'order_reference'    => ['sometimes','nullable','string','max:128'],
            'order_meta'         => ['sometimes','nullable','array'],
        ]);

        $initialMajor = (float) $data['initial_amount'];
        $initialMinor = (int) round($initialMajor * 100);
        $balanceMinor = array_key_exists('balance', $data)
            ? (int) round(((float) $data['balance']) * 100)
            : $initialMinor;

        $giftcard = new Giftcard();
        $giftcard->public_id = (string) Str::uuid();
        $giftcard->code = GiftcardCode::make();
        $giftcard->initial_amount = $initialMinor;
        $giftcard->balance = max(0, $balanceMinor);
        $giftcard->currency = strtoupper($data['currency'] ?? 'ISK');
        $giftcard->status = $data['status'] ?? 'active';
        $giftcard->expires_at = $data['expires_at'] ?? null;
        $giftcard->meta = $data['meta'] ?? null;
        $giftcard->passkit_program_id = $data['passkit_program_id'] ?? null;
        $giftcard->passkit_member_id = $data['passkit_member_id'] ?? null;
        $giftcard->save();

        // Record initial credit transaction (optionally linked to Woo order)
        try {
            $reference = $data['order_reference'] ?? (isset($data['order_id']) ? ('wc:' . (string) $data['order_id']) : null);
            GiftcardTransaction::create([
                'giftcard_id' => $giftcard->id,
                'type'        => 'credit',
                'amount'      => (int) $giftcard->initial_amount,
                'reference'   => $reference,
                'details'     => $data['order_meta'] ?? (isset($data['order_id']) ? ['order_id' => $data['order_id']] : null),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to create initial giftcard transaction', [
                'giftcard_id' => $giftcard->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Attempt PassKit enrolment (best-effort)
        $passkitResponse = null; // capture raw response to return to client
        try {
            $meta = Arr::wrap($giftcard->meta);
            $ownerEmail = Arr::get($meta, 'owner_email');
            $ownerName  = trim((string) Arr::get($meta, 'owner_name', ''));
            $ownerPhone = Arr::get($meta, 'owner_phone');

            $person = [];
            if ($ownerName !== '') {
                $person['displayName'] = $ownerName;
                $parts = preg_split('/\\s+/', $ownerName, -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($parts)) {
                    $person['forename'] = array_shift($parts);
                    if (!empty($parts)) {
                        $person['surname'] = implode(' ', $parts);
                    }
                }
            }
            if (!empty($ownerPhone)) {
                $person['mobileNumber'] = $ownerPhone;
            }

            $metaData = array_filter([
                'currency'       => $giftcard->currency,
                'site'           => config('app.url'),
                'orderId'        => isset($data['order_id']) ? (string) $data['order_id'] : null,
                'orderReference' => $data['order_reference'] ?? null,
            ], static fn ($value) => !is_null($value) && $value !== '');

            $extra = array_filter([
                'person'     => $person,
                'metaData'   => $metaData,
                'status'     => 'ENROLLED',
                'expiryDate' => $giftcard->expires_at ? $giftcard->expires_at->toAtomString() : null,
            ], static fn ($value) => !is_null($value) && $value !== [] && $value !== '');

            $result = $passkit->enrolMember(
                externalId: $giftcard->code,
                email: $ownerEmail,
                pointsCents: max(0, (int) round($initialMajor)),
                customFields: [],
                extra: $extra,
            );
            $passkitResponse = $result;

            $giftcard->passkit_program_id = config('passkit.program_id') ?: $giftcard->passkit_program_id;
            if (!empty($result['pass_id'])) {
                $giftcard->passkit_member_id = (string) $result['pass_id'];
            }
            if (!empty($result['url'])) {
                $meta = (array) ($giftcard->meta ?? []);
                $meta['pass_url'] = $result['url'];
                $giftcard->meta = $meta;
            }
            $giftcard->save();
        } catch (\Throwable $e) {
            // Log and continue; giftcard remains created even if PassKit fails
            \Log::error('PassKit enrolment failed', [
                'giftcard_id' => $giftcard->id,
                'code' => $giftcard->code,
                'error' => $e->getMessage(),
            ]);
            $passkitResponse = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $giftcard->refresh();

        return response()->json([
            'giftcard' => $this->serializeGiftcard($giftcard),
            'passkit' => $passkitResponse,
        ], 201);
    }

    public function update(Request $request, Giftcard $giftcard)
    {
        $data = $request->validate([
            'amount'    => ['required','numeric','min:0.01'],
            'reference' => ['sometimes','nullable','string','max:128'],
        ]);

        $amount = (float) $data['amount'];
        $amountMinor = (int) round($amount * 100);
        $previousBalance = (int) $giftcard->balance;

        if ($amountMinor <= 0) {
            return response()->json([
                'message' => 'Amount must be greater than zero.',
                'errors' => [
                    'amount' => ['The amount must convert to a positive value.'],
                ],
            ], 422);
        }

        if ($amountMinor > $previousBalance) {
            return response()->json([
                'message' => 'Amount exceeds available balance.',
                'errors' => [
                    'amount' => ['The requested amount exceeds the giftcard balance.'],
                ],
            ], 422);
        }

        $giftcard->balance = $previousBalance - $amountMinor;
        if ($giftcard->balance === 0) {
            $giftcard->status = 'inactive';
        }
        $giftcard->save();

        GiftcardTransaction::create([
            'giftcard_id' => $giftcard->id,
            'type'        => 'debit',
            'amount'      => $amountMinor,
            'reference'   => $data['reference'] ?? null,
            'details'     => [
                'reason'          => 'api_debit',
                'amount_major'    => $amount,
                'amount_minor'    => $amountMinor,
                'balance_before'  => $previousBalance,
                'balance_after'   => $giftcard->balance,
                'currency'        => $giftcard->currency,
            ],
        ]);

        $giftcard->refresh();

        return response()->json($this->serializeGiftcard($giftcard));
    }

    public function destroy(Giftcard $giftcard)
    {
        $giftcard->delete();
        return response()->noContent();
    }

    private function serializeGiftcard(Giftcard $giftcard): array
    {
        $data = $giftcard->toArray();
        $data['initial_amount'] = $this->minorToMajor($giftcard->initial_amount);
        $data['balance'] = $this->minorToMajor($giftcard->balance);

        return $data;
    }

    private function minorToMajor(?int $amount): float
    {
        if ($amount === null) {
            return 0.0;
        }

        return round($amount / 100, 2);
    }

}

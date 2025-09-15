<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Giftcard;
use App\Services\GiftcardCode;
use App\Services\PasskitService;
use App\Models\GiftcardTransaction;
use Illuminate\Http\Request;
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
        return response()->json($giftcards);
    }

    public function show(Giftcard $giftcard)
    {
        return response()->json($giftcard);
    }

    public function store(Request $request, PasskitService $passkit)
    {
        $data = $request->validate([
            'initial_amount' => ['required','integer','min:0'],
            'balance'        => ['nullable','integer','min:0'],
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

        $giftcard = new Giftcard();
        $giftcard->public_id = (string) Str::uuid();
        $giftcard->code = GiftcardCode::make();
        $giftcard->initial_amount = $data['initial_amount'];
        $giftcard->balance = $data['balance'] ?? $data['initial_amount'];
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
            $ownerEmail = is_array($giftcard->meta ?? null) ? ($giftcard->meta['owner_email'] ?? null) : null;
            $ownerName  = is_array($giftcard->meta ?? null) ? ($giftcard->meta['owner_name'] ?? null) : null;

            $person = [];
            if ($ownerName) {
                $person['displayName'] = $ownerName;
            }

            $extra = [
                'person'     => $person,
                'metaData'   => [
                    'currency' => $giftcard->currency,
                    'site'     => config('app.url'),
                ],
                'status'     => 'ENROLLED',
                'expiryDate' => $giftcard->expires_at ? $giftcard->expires_at->toAtomString() : null,
            ];

            $result = $passkit->enrolMember(
                externalId: $giftcard->code,
                email: $ownerEmail,
                pointsCents: (int) $giftcard->initial_amount,
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

        return response()->json([
            'giftcard' => $giftcard,
            'passkit' => $passkitResponse,
        ], 201);
    }

    public function update(Request $request, Giftcard $giftcard)
    {
        $data = $request->validate([
            'initial_amount' => ['sometimes','integer','min:0'],
            'balance'        => ['sometimes','integer','min:0'],
            'currency'       => ['sometimes','string','size:3'],
            'status'         => ['sometimes', Rule::in(['active','inactive','redeemed','expired'])],
            'expires_at'     => ['sometimes','nullable','date'],
            'meta'           => ['sometimes','nullable','array'],
            'passkit_program_id' => ['sometimes','nullable','string','max:64'],
            'passkit_member_id'  => ['sometimes','nullable','string','max:64'],
        ]);

        if (array_key_exists('initial_amount', $data)) {
            $giftcard->initial_amount = $data['initial_amount'];
        }
        if (array_key_exists('balance', $data)) {
            $giftcard->balance = $data['balance'];
        }
        if (array_key_exists('currency', $data)) {
            $giftcard->currency = strtoupper($data['currency']);
        }
        if (array_key_exists('status', $data)) {
            $giftcard->status = $data['status'];
        }
        if (array_key_exists('expires_at', $data)) {
            $giftcard->expires_at = $data['expires_at'];
        }
        if (array_key_exists('meta', $data)) {
            $giftcard->meta = $data['meta'];
        }
        if (array_key_exists('passkit_program_id', $data)) {
            $giftcard->passkit_program_id = $data['passkit_program_id'];
        }
        if (array_key_exists('passkit_member_id', $data)) {
            $giftcard->passkit_member_id = $data['passkit_member_id'];
        }

        $giftcard->save();

        return response()->json($giftcard);
    }

    public function destroy(Giftcard $giftcard)
    {
        $giftcard->delete();
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Giftcard;
use App\Services\GiftcardCode;
use App\Services\PasskitService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
            'status'         => ['nullable', Rule::in(['active','blocked','expired'])],
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
        $giftcard->code = GiftcardCode::make();
        $giftcard->initial_amount_cents = 0;
        $giftcard->balance_cents = 0;
        $giftcard->currency = strtoupper($data['currency'] ?? 'ISK');
        $giftcard->status = $data['status'] ?? 'active';
        $giftcard->expires_at = $data['expires_at'] ?? null;
        $giftcard->meta = $data['meta'] ?? null;
        $giftcard->passkit_program_id = $data['passkit_program_id'] ?? null;
        $giftcard->passkit_member_id = $data['passkit_member_id'] ?? null;
        $giftcard->save();

        $reference = $data['order_reference'] ?? (isset($data['order_id']) ? ('wc:' . (string) $data['order_id']) : null);
        $orderDetails = array_filter([
            'order_id' => $data['order_id'] ?? null,
            'order_reference' => $data['order_reference'] ?? null,
            'order_meta' => $data['order_meta'] ?? null,
        ], static fn ($value) => !is_null($value));

        $woocommerceOrderId = isset($data['order_id']) ? (int) $data['order_id'] : null;

        if ($initialMinor > 0) {
            try {
                $giftcard->issue($initialMinor, $reference, array_merge([
                    'reason' => 'initial_credit',
                ], $orderDetails), $woocommerceOrderId);
            } catch (\Throwable $e) {
                \Log::error('Failed to create initial giftcard transaction', [
                    'giftcard_id' => $giftcard->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $delta = $balanceMinor - (int) $giftcard->balance;
        if ($delta !== 0) {
            try {
                $adjustmentDetails = [
                    'reason' => 'initial_balance_adjustment',
                    'requested_balance_minor' => $balanceMinor,
                    'requested_balance_major' => $this->minorToMajor($balanceMinor),
                ];

                $giftcard->adjust($delta, 'initial_balance_adjustment', $adjustmentDetails);
            } catch (\Throwable $e) {
                \Log::error('Failed to reconcile giftcard balance', [
                    'giftcard_id' => $giftcard->id,
                    'error' => $e->getMessage(),
                ]);
            }
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

    public function update(Request $request, Giftcard $giftcard, PasskitService $passkit)
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

        try {
            $giftcard->redeem(
                $amountMinor,
                $data['reference'] ?? null,
                [
                    'reason' => 'api_debit',
                    'amount_major' => $amount,
                    'requested_by' => $request->user()?->id,
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [
                    'amount' => [$e->getMessage()],
                ],
            ], 422);
        }

        $passkitMemberId = $giftcard->passkit_member_id;
        if (!empty($passkitMemberId)) {
            $meta = Arr::wrap($giftcard->meta);
            $person = [];
            $ownerEmail = Arr::get($meta, 'owner_email');
            $defaultEmail = config('passkit.default_email');
            if (empty($ownerEmail) && !empty($defaultEmail)) {
                $ownerEmail = $defaultEmail;
            }
            if (!empty($ownerEmail)) {
                $person['emailAddress'] = $ownerEmail;
            }

            $ownerName = trim((string) Arr::get($meta, 'owner_name', ''));
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

            $ownerPhone = Arr::get($meta, 'owner_phone');
            if (!empty($ownerPhone)) {
                $person['mobileNumber'] = $ownerPhone;
            }

            $metaData = array_filter([
                'currency' => $giftcard->currency,
                'site' => config('app.url'),
            ], static fn ($value) => !is_null($value) && $value !== '');

            $expiry = $giftcard->expires_at ? $giftcard->expires_at->toAtomString() : null;

            try {
                $passkit->updateMemberPoints(
                    $passkitMemberId,
                    $giftcard->code,
                    $this->minorToMajor($giftcard->balance),
                    $person,
                    $metaData,
                    $expiry,
                );
            } catch (\Throwable $e) {
                \Log::error('PassKit balance sync failed', [
                    'giftcard_id' => $giftcard->id,
                    'passkit_member_id' => $passkitMemberId,
                    'passkit_external_id' => $giftcard->code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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
        $data['initial_amount'] = $this->minorToMajor($giftcard->initial_amount_cents);
        $data['balance'] = $this->minorToMajor($giftcard->balance_cents);

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

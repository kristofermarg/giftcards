<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasskitService
{
    private string $apiBase;
    private string $programId;
    private string $tierId;
    private string $publicBase;
    private bool $debug;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->apiBase    = rtrim((string) config('passkit.api_base', ''), '/');
        $this->programId  = (string) config('passkit.program_id', '');
        $this->tierId     = (string) config('passkit.tier_id', 'gift_card');
        $this->publicBase = rtrim((string) config('passkit.public_base', ''), '/') . '/';
        $this->debug      = (bool) config('passkit.debug', false);
        $this->apiKey     = (string) config('passkit.api_key', '');
        $this->apiSecret  = (string) config('passkit.api_secret', '');
    }

    public function enrolMember(
        string $externalId,
        ?string $email = null,
        ?int $pointsCents = null,
        array $customFields = [],
        array $extra = []
    ): array {
        $person = Arr::get($extra, 'person', []);
        if (!empty($email)) {
            $person['emailAddress'] = $email;
        }

        $member = [
            'externalId'         => (string) $externalId,
            'groupingIdentifier' => Arr::get($extra, 'groupingIdentifier'),
            'tierId'             => $this->tierId,
            'programId'          => $this->programId,

            'person'   => $person ?: null,
            'metaData' => Arr::get($extra, 'metaData') ?: null,
            'optOut'   => Arr::has($extra, 'optOut') ? (bool) Arr::get($extra, 'optOut') : null,

            'points'          => is_int($pointsCents) ? $pointsCents : null,
            'secondaryPoints' => Arr::get($extra, 'secondaryPoints'),
            'tierPoints'      => Arr::get($extra, 'tierPoints'),

            'expiryDate'         => Arr::get($extra, 'expiryDate'),
            'status'             => Arr::get($extra, 'status', 'ENROLLED'),
            'currentTierAwarded' => Arr::get($extra, 'currentTierAwarded'),
            'currentTierExpires' => Arr::get($extra, 'currentTierExpires'),

            'passOverrides' => Arr::get($extra, 'passOverrides'),
            'passMetaData'  => Arr::get($extra, 'passMetaData'),
            'notes'         => Arr::get($extra, 'notes'),

            'profileImage' => Arr::get($extra, 'profileImage'),
            'operation'    => Arr::get($extra, 'operation'),
        ];

        if (!empty($customFields)) {
            $member = array_merge($member, $customFields);
        }

        $payload = $this->arrayCompact($member);
        $resp = $this->request('POST', '/members/member', $payload);

        $passId  = is_array($resp) ? ($resp['id'] ?? ($resp['member']['id'] ?? null)) : null;
        $passUrl = is_array($resp)
            ? ($resp['passUrl'] ?? ($passId ? $this->publicBase . $passId : null))
            : null;

        return ['raw' => $resp, 'pass_id' => $passId, 'url' => $passUrl];
    }

    public function updateMemberPoints(string $memberId, float $points): void
    {
        $payload = [[
            'op' => 'replace',
            'path' => '/members/member/points',
            'value' => max(0, round($points, 2)),
        ]];

        $this->request('PATCH', '/members/member/' . rawurlencode($memberId), $payload);
    }

    private function request(string $method, string $path, ?array $body = null)
    {
        $url = $this->apiBase . '/' . ltrim($path, '/');
        $rid = (string) Str::uuid();
        $auth = $this->buildJwt();

        $contentType = $method === 'PATCH' ? 'application/json-patch+json' : 'application/json';

        $headers = [
            'Authorization' => $auth,
            'Accept'        => 'application/json',
            'Content-Type'  => $contentType,
            'X-Request-Id'  => $rid,
        ];

        if ($this->debug) {
            Log::debug('PassKit request', [
                'rid' => $rid,
                'method' => $method,
                'url' => $url,
                'headers' => ['Authorization' => '[REDACTED]'] + $headers,
                'body' => $body,
            ]);
        }

        $req = Http::withHeaders($headers)->timeout(20);
        $res = $req->send($method, $url, !is_null($body) ? ['json' => $body] : []);

        if ($this->debug) {
            Log::debug('PassKit response', [
                'rid' => $rid,
                'status' => $res->status(),
                'headers' => $res->headers(),
                'body' => $this->truncate((string) $res->body(), 4000),
            ]);
        }

        if ($res->failed()) {
            $body = $res->json();
            if (is_null($body)) {
                $body = (string) $res->body();
            }

            Log::error('PassKit error', [
                'rid' => $rid,
                'status' => $res->status(),
                'body' => $body,
                'url' => $url,
            ]);

            $msg = 'PassKit HTTP ' . $res->status();
            if (is_array($body)) {
                $msg .= ': ' . json_encode($body);
            } elseif (is_string($body) && $body !== '') {
                $msg .= ': ' . $body;
            }

            throw new \RuntimeException($msg);
        }
        return $res->json() ?? (string) $res->body();
    }

    private function buildJwt(): string
    {
        $key = $this->apiKey;
        $secret = $this->apiSecret;
        if (empty($key) || empty($secret)) {
            throw new \RuntimeException('PassKit API credentials missing');
        }

        $now = time();
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = ['uid' => $key, 'iat' => $now, 'exp' => $now + 3600];

        $segments = [
            $this->b64url(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->b64url(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = $this->b64url($signature);
        return implode('.', $segments);
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function truncate(string $s, int $limit): string
    {
        return strlen($s) > $limit ? substr($s, 0, $limit) . '...[truncated]' : $s;
    }

    private function arrayCompact($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $vv = $this->arrayCompact($v);
                if ($vv === null) continue;
                if (is_array($vv) && $vv === []) continue;
                $out[$k] = $vv;
            }
            return $out;
        }
        return $value === null ? null : $value;
    }
}


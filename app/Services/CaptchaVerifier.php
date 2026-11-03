<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CaptchaVerifier
{
    public function verify(?string $token, string $action, ?string $ip = null): void
    {
        if (! config('tourism.captcha.enabled')) {
            if (app()->environment(['local', 'testing'])) {
                return;
            }
            throw ValidationException::withMessages(['captcha' => 'Verification is unavailable. Please contact the operator.']);
        }
        $valid = false;
        if ($token && strlen($token) <= 2048) {
            try {
                $response = Http::timeout(8)->asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('tourism.captcha.secret'), 'response' => $token, 'remoteip' => $ip, 'idempotency_key' => (string) Str::uuid(),
                ]);
                $body = $response->json();
                $valid = $response->successful() && ($body['success'] ?? false) === true && ($body['action'] ?? null) === $action
                    && in_array($body['hostname'] ?? '', config('tourism.captcha.hostnames'), true);
            } catch (\Throwable $e) {
                report(new \RuntimeException('Anti-bot verification provider unavailable.'));
            }
        }
        if (! $valid) {
            throw ValidationException::withMessages(['captcha' => 'Verification failed or expired. Please try again.']);
        }
    }
}

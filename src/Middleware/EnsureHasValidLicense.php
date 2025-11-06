<?php

namespace Hearth\LicenseClient\Middleware;

use Closure;
use Hearth\LicenseClient\Encryption;

class EnsureHasValidLicense
{
    public function handle($request, Closure $next)
    {
        // Allow in console (artisan) so CLI tasks continue to work. For web
        // requests, do not allow exceptions: block until a valid license exists.
        if (app()->runningInConsole()) {
            return $next($request);
        }

        $store = storage_path('license.json');
        if (!file_exists($store)) {
            // If this app is the authority itself (authority_url matches app URL),
            // allow a local DB-backed fallback so admins can approve licenses
            // from the same app without manually running the install command.
            try {
                $authorityUrl = config('license-client.authority_url') ?? config('license-client.authority') ?? null;
                $appUrl = config('app.url') ?? env('APP_URL', '');
                $authHost = $authorityUrl ? parse_url(rtrim($authorityUrl, '/'), PHP_URL_HOST) : null;
                $appHost = parse_url($appUrl, PHP_URL_HOST) ?: gethostname();

                if ($authHost && $authHost === $appHost) {
                    // Try to find an active license in the local database for this host
                    if (class_exists('\App\\Models\\License')) {
                        $licenseModel = \App\Models\License::where('domain', $appHost)
                            ->where('is_active', true)
                            ->first();
                        if ($licenseModel && (empty($licenseModel->expires_at) || strtotime($licenseModel->expires_at) > time())) {
                            // Optionally cache/write a local license.json so subsequent requests
                            // use the file path check. We'll write a minimal encrypted payload.
                            try {
                                $payload = [
                                    'license_key' => $licenseModel->license_key,
                                    'domain' => $licenseModel->domain,
                                    'data' => [
                                        'valid' => true,
                                        'message' => 'License active (local DB fallback)',
                                        'expires_at' => $licenseModel->expires_at?->toIso8601String() ?? null,
                                    ],
                                    'fetched_at' => now()->toIso8601String(),
                                    'authority' => $authorityUrl ?: $appUrl,
                                ];
                                $plaintext = json_encode($payload, JSON_UNESCAPED_SLASHES);
                                $encrypted = \Hearth\LicenseClient\Encryption::encryptString($plaintext);
                                $wrapper = json_encode([
                                    'encrypted' => true,
                                    'version' => 1,
                                    'payload' => $encrypted,
                                ], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
                                @file_put_contents($store, $wrapper);
                            } catch (\Throwable $e) {
                                // ignore write errors and just allow the request
                            }

                            return $next($request);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore and fall through to blocked response below
            }

            $message = \Hearth\LicenseClient\Messages::get('not_present');
            return response()->view('license-client::blocked', ['message' => $message], 403);
        }

        $raw = file_get_contents($store);
        if (empty($raw)) {
            return response('License required', 403);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded['payload']) || empty($decoded['encrypted'])) {
            $message = \Hearth\LicenseClient\Messages::get('invalid');
            return response()->view('license-client::blocked', ['message' => $message], 403);
        }

        try {
            $plaintext = Encryption::decryptString($decoded['payload']);
            $obj = json_decode($plaintext, true);
        } catch (Throwable $e) {
            $message = \Hearth\LicenseClient\Messages::get('invalid');
            return response()->view('license-client::blocked', ['message' => $message], 403);
        }

        if (empty($obj) || empty($obj['license_key'])) {
            $message = \Hearth\LicenseClient\Messages::get('invalid');
            return response()->view('license-client::blocked', ['message' => $message], 403);
        }

        // Require authority-declared validity flag
        $validFlag = $obj['data']['valid'] ?? null;
        if ($validFlag !== true) {
            $message = \Hearth\LicenseClient\Messages::get('not_active');
            return response()->view('license-client::blocked', ['message' => $message], 403);
        }

        // optional: expire check if present
        $expires = $obj['data']['expires_at'] ?? $obj['data']['expires'] ?? null;
        if ($expires) {
            try {
                $expTs = strtotime($expires);
                if ($expTs !== false && $expTs < time()) {
                    $message = config('license-client.messages.expired');
                    return response()->view('license-client::blocked', ['message' => $message], 403);
                }
            } catch (\Throwable $e) {
                // ignore parse errors and allow if not parseable
            }
        }

        // optional: domain match
        $appUrl = config('app.url') ?? env('APP_URL', '');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: gethostname();
        if (!empty($obj['domain']) && $obj['domain'] !== $host) {
            $message = \Hearth\LicenseClient\Messages::get('domain_mismatch');
            return response()->view('license-client::blocked', ['message' => $message], 403);
        }

        // license looks okay, allow request
        return $next($request);
    }
}

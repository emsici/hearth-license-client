<?php

namespace Hearth\LicenseClient\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hearth\LicenseClient\Encryption;

class PushLicenseController extends Controller
{
    /**
     * Receive a pushed, signed license payload from the authority.
     * Expected JSON: { "payload": "{...}", "signature": "base64...", "kid": "optional" }
     */
    public function receive(Request $request)
    {
        $payload = $request->input('payload');
        $signature = $request->input('signature');

        if (empty($payload) || empty($signature)) {
            return response()->json(['error' => 'payload and signature required'], 400);
        }

        $pubPath = __DIR__ . '/../../keys/public.pem';
        $kid = $request->input('kid');

        // Attempt JWKS-first if authority URL configured and kid present.
    $pubKeyPem = null;
    $authority = \Hearth\LicenseClient\Package::authorityUrl() ?? null;

        if (!empty($kid) && !empty($authority)) {
            try {
                $jwksResp = \Illuminate\Support\Facades\Http::timeout(\Hearth\LicenseClient\Package::remoteTimeout())->get(rtrim($authority, '/') . '/.well-known/jwks.json');
                if ($jwksResp->successful()) {
                    $jwks = $jwksResp->json();
                    foreach ($jwks['keys'] ?? [] as $jwk) {
                        if (!empty($jwk['kid']) && $jwk['kid'] === $kid) {
                            // Prefer x5c certificate if present
                            if (!empty($jwk['x5c'][0])) {
                                $cert = chunk_split($jwk['x5c'][0], 64, "\n");
                                $certPem = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";
                                if (openssl_pkey_get_public($certPem)) {
                                    $pubKeyPem = $certPem;
                                }
                            }

                            // If x5c not present, we can't reliably construct PEM here; fallback to bundled key below
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                logger()->debug('Failed to fetch JWKS during push verification: ' . $e->getMessage());
            }
        }

        // Fallback: use bundled public.pem if JWKS did not provide an x5c cert
        if (empty($pubKeyPem)) {
            if (!file_exists($pubPath)) {
                return response()->json(['error' => 'server public key not available; JWKS did not provide x5c and no bundled key found'], 500);
            }
            $pubKeyPem = file_get_contents($pubPath);
        }

        $sigRaw = base64_decode($signature, true);
        if ($sigRaw === false) {
            return response()->json(['error' => 'invalid signature encoding'], 400);
        }

        $pub = openssl_pkey_get_public($pubKeyPem);
        if ($pub === false) {
            return response()->json(['error' => 'invalid public key'], 500);
        }

        $verified = openssl_verify($payload, $sigRaw, $pub, OPENSSL_ALGO_SHA256) === 1;
        openssl_free_key($pub);

        if (! $verified) {
            return response()->json(['error' => 'signature verification failed'], 403);
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded) || empty($decoded['domain']) || empty($decoded['license_key'])) {
            return response()->json(['error' => 'invalid payload format'], 400);
        }

        // Ensure payload domain matches this app host
        $appUrl = config('app.url') ?? env('APP_URL', '');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: gethostname();
        if ($decoded['domain'] !== $host) {
            return response()->json(['error' => 'domain mismatch'], 403);
        }

        // Prepare wrapper and write encrypted storage file
        try {
            $plaintext = json_encode([
                'license_key' => $decoded['license_key'],
                'domain' => $decoded['domain'],
                'data' => $decoded['data'] ?? [],
                'fetched_at' => now()->toIso8601String(),
                'authority' => \Hearth\LicenseClient\Package::authorityUrl() ?? null,
            ], JSON_UNESCAPED_SLASHES);

            $encrypted = Encryption::encryptString($plaintext);
            $wrapper = json_encode([
                'encrypted' => true,
                'version' => 1,
                'payload' => $encrypted,
            ], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

            file_put_contents(storage_path('license.json'), $wrapper);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'failed to save license', 'detail' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => true], 200);
    }

    /**
     * Simple health check for the push endpoint (GET /.well-known/push-license).
     */
    public function health()
    {
        return response()->json(['ok' => true, 'service' => 'push-license'], 200);
    }
}

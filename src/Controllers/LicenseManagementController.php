<?php

namespace Hearth\LicenseClient\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hearth\LicenseClient\Encryption;

class LicenseManagementController extends Controller
{
    public function index()
    {
        $path = storage_path('license.json');
        $license = null;
        $error = null;

        if (!file_exists($path)) {
            return view('license-client::licente.index', ['license' => null, 'error' => null]);
        }

        try {
            $raw = file_get_contents($path);
            $wrapper = json_decode($raw, true);
            if (!is_array($wrapper) || empty($wrapper['payload'])) {
                $error = 'Fișierul de licență este corupt sau format neașteptat.';
            } else {
                $decrypted = Encryption::decryptString($wrapper['payload']);
                $license = json_decode($decrypted, true);
            }
        } catch (\Throwable $e) {
            $error = 'Eroare la citirea licenței: ' . $e->getMessage();
        }

        return view('license-client::licente.index', ['license' => $license, 'error' => $error]);
    }

    /**
     * POST /licente/verify — a lightweight verify action that simply ensures the stored
     * license can be decrypted. More advanced server checks can be implemented later.
     */
    public function verify(Request $request)
    {
        $path = storage_path('license.json');
        if (!file_exists($path)) {
            return redirect()->route('license-client.licente.index')->with('error', 'Nu există nicio licență instalată.');
        }

        try {
            $raw = file_get_contents($path);
            $wrapper = json_decode($raw, true);
            if (!is_array($wrapper) || empty($wrapper['payload'])) {
                return redirect()->route('license-client.licente.index')->with('error', 'Fișierul de licență este corupt.');
            }
            $decrypted = Encryption::decryptString($wrapper['payload']);
            // If decrypt didn't throw, consider it valid for now
            return redirect()->route('license-client.licente.index')->with('success', 'Licența a fost decriptată cu succes.');
        } catch (\Throwable $e) {
            return redirect()->route('license-client.licente.index')->with('error', 'Verificare eșuată: ' . $e->getMessage());
        }
    }

    /**
     * POST /licente/upload — accept a manual license key from the admin UI.
     * Body: license_key=STRING
     */
    public function upload(Request $request)
    {
        $request->validate(['license_key' => 'required|string']);

        $key = trim($request->input('license_key'));

        // Create a minimal license payload and save it encrypted. This allows
        // administrators to paste an authority-provided license key.
        $payload = [
            'license_key' => $key,
            'domain' => parse_url(config('app.url') ?? env('APP_URL', ''), PHP_URL_HOST) ?: gethostname(),
            'data' => ['valid' => true, 'issued_by_manual_upload' => true],
            'fetched_at' => now()->toIso8601String(),
        ];

        try {
            $plaintext = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $encrypted = Encryption::encryptString($plaintext);
            $wrapper = json_encode([
                'encrypted' => true,
                'version' => 1,
                'payload' => $encrypted,
            ], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

            file_put_contents(storage_path('license.json'), $wrapper);
            return redirect()->route('license-client.licente.index')->with('success', 'Licența a fost instalată local.');
        } catch (\Throwable $e) {
            return redirect()->route('license-client.licente.index')->with('error', 'Eroare la instalare: ' . $e->getMessage());
        }
    }

    public function destroy()
    {
        $path = storage_path('license.json');
        if (file_exists($path)) {
            try {
                unlink($path);
                return redirect()->route('license-client.licente.index')->with('success', 'Fișierul de licență a fost șters.');
            } catch (\Throwable $e) {
                return redirect()->route('license-client.licente.index')->with('error', 'Eroare la ștergere: ' . $e->getMessage());
            }
        }

        return redirect()->route('license-client.licente.index')->with('error', 'Nu există fișier de licență de șters.');
    }
}

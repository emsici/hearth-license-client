# Hearth License Client

Small Laravel package that lets a client application verify a license key against hearth.master-data.ro and store the verified license locally.

Usage (client application):

1. Add the package to your project's composer (for local testing, add a path repository):

```json
"repositories": [
  {
    "type": "path",
    "url": "../path/to/hearth/master-data/sdk/laravel-license-client"
  }
]
```

then:

```bash
composer require hearth/license-client
```

2. Run the artisan command to verify a license key (this will contact hearth.master-data.ro):

```bash
php artisan make:license-server YOUR-LICENSE-KEY
```

3. On success the package writes `storage/license.json` with the verified license metadata.

Notes:
- The package fetches the authority public key from `https://hearth.master-data.ro/keys/pem` and verifies the signature on the response.
- This package is intentionally minimal and only used by clients; it does not alter the server.

Encryption and middleware
- The package now saves the verified license encrypted by default using the application's `APP_KEY` (AES-256-CBC). The saved file is `storage/license.json` and contains a small JSON wrapper with the encrypted payload.
- A middleware stub `Hearth\\LicenseClient\\Middleware\\EnsureHasValidLicense` is included in the package. Register it in your app and it will return HTTP 403 when the license file is missing, invalid, expired, or domain-mismatched.

Usage examples:

1. Verify and save (the saved file will be encrypted using `APP_KEY`):

```bash
php artisan make:license-server YOUR-LICENSE-KEY
```

2. Register middleware in `app/Http/Kernel.php` or apply to routes:

```php
use Hearth\LicenseClient\Middleware\EnsureHasValidLicense;

//'web' => [
//    \App\Http\Middleware\EncryptCookies::class,
//    ...
//    EnsureHasValidLicense::class,
//],
```

3. If you prefer not to use `APP_KEY`, set `APP_LICENSE_PASSPHRASE` in your environment and the package will derive the encryption key from that value instead.

Notes on security:
- `APP_KEY` is a reasonable default for encrypting a local file inside the application, but for extra safety you can provide a dedicated `APP_LICENSE_PASSPHRASE` environment variable and back it up securely.
- If you need key rotation, the package can be extended to store an encrypted key-wrapping key; ask me and I can add rotation support.
# hearth-license-client

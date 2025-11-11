# Hearth License Client# Hearth License Client



Mic pachet Laravel folosit pe partea de client care verifică o licență emisă de hearth.master-data.ro și salvează local metadatele licenței verificate.Small Laravel package that lets a client application verify a license key against hearth.master-data.ro and store the verified license locally.



Instalare (aplicație client):Usage (client application):



1) Adaugă pachetul în `composer.json` folosind un repository de tip `path` (pentru testare locală):1. Add the package to your project's composer (for local testing, add a path repository):



```json```json

"repositories": ["repositories": [

  {  {

    "type": "path",    "type": "path",

    "url": "../cale/către/hearth/master-data/sdk/laravel-license-client"    "url": "../path/to/hearth/master-data/sdk/laravel-license-client"

  }  }

]]

``````



Apoi rulează:then:



```bash```bash

composer require hearth/license-clientcomposer require hearth/license-client

``````



2) Rulează comanda artisan pentru a valida o cheie de licență (comandă va contacta hearth.master-data.ro):2. Run the artisan command to verify a license key (this will contact hearth.master-data.ro):



```bash```bash

php artisan make:license-server YOUR-LICENSE-KEYphp artisan make:license-server YOUR-LICENSE-KEY

``````



3) La succes, pachetul va salva fișierul `storage/license.json` cu metadatele licenței (criptat).3. On success the package writes `storage/license.json` with the verified license metadata.



Ce face pachetulNotes:

- Obține cheia publică a autorității de la: `https://hearth.master-data.ro/keys/pem` și verifică semnătura payload-ului.- The package fetches the authority public key from `https://hearth.master-data.ro/keys/pem` and verifies the signature on the response.

- Salvează licența verificată local, criptată (implicit folosind `APP_KEY`).- This package is intentionally minimal and only used by clients; it does not alter the server.

- Înregistrează middleware-ul `Hearth\\LicenseClient\\Middleware\\EnsureHasValidLicense` în grupa `web` la boot (prin design aplicația client va returna 403 până când licența este validă).

Encryption and middleware

Criptare și middleware- The package saves the verified license encrypted by default using the application's `APP_KEY` (AES-256-CBC). The saved file is `storage/license.json` and contains a small JSON wrapper with the encrypted payload.

- Fișierul `storage/license.json` este criptat folosind AES-256-CBC; implicit se folosește `APP_KEY`.- By default the package will *enforce* the license check automatically: when the package is registered it pushes `Hearth\\LicenseClient\\Middleware\\EnsureHasValidLicense` into the `web` middleware group and the application will return HTTP 403 for web requests until a valid license is present.

- Dacă preferi o parolă separată, definește variabila de mediu `APP_LICENSE_PASSPHRASE` și pachetul va deriva cheia de criptare din ea.

Enforcement (mandatory)

Comportament de enforcement- The package enforces the license check automatically and this behavior is mandatory: the middleware is added to the `web` group on package boot and web requests will receive HTTP 403 until a valid license is present. This cannot be disabled from the environment by design.

- Middleware-ul este intenționat proiectat să fie aplicat automat în grupa `web`. Dacă ai endpoint-uri interne (health, admin tools) pe care vrei să le excludi, implementează o prioritate mai mare pentru un middleware local care le permite explicit înainte de middleware-ul pachetului.

If you need to exempt specific internal tooling or health endpoints, perform that logic in your own application before the middleware runs (for example register a higher-priority middleware), but the package itself will not provide an opt-out.

Exemple rapide

1) Verificare și salvare:Usage examples:



```bash1. Verify and save (the saved file will be encrypted using `APP_KEY`):

php artisan make:license-server YOUR-LICENSE-KEY

``````bash

php artisan make:license-server YOUR-LICENSE-KEY

2) Dacă vrei să aplici middleware manual în `app/Http/Kernel.php`:```



```php2. Register middleware in `app/Http/Kernel.php` or apply to routes:

use Hearth\\LicenseClient\\Middleware\\EnsureHasValidLicense;

```php

'web' => [use Hearth\LicenseClient\Middleware\EnsureHasValidLicense;

    \App\Http\\Middleware\\EncryptCookies::class,

    // ...//'web' => [

    EnsureHasValidLicense::class,//    \App\Http\Middleware\EncryptCookies::class,

],//    ...

```//    EnsureHasValidLicense::class,

//],

Sugestii de bune practici```

- Păstrează `APP_KEY` securizat. Pentru un plus de securitate folosește `APP_LICENSE_PASSPHRASE` dedicat.

- Pe serverele de producție, asigură-te că endpoint-ul `.well-known/push-license` este accesibil de la hearth și că firewall-urile permit conexiunile.3. If you prefer not to use `APP_KEY`, set `APP_LICENSE_PASSPHRASE` in your environment and the package will derive the encryption key from that value instead.



Dacă vrei, pot adăuga un fișier de configurare cu exemple în română, teste automate și instrucțiuni detaliate de integrare.Notes on security:

- `APP_KEY` is a reasonable default for encrypting a local file inside the application, but for extra safety you can provide a dedicated `APP_LICENSE_PASSPHRASE` environment variable and back it up securely.
- If you need key rotation, the package can be extended to store an encrypted key-wrapping key; ask me and I can add rotation support.
# hearth-license-client

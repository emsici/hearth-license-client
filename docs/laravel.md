# Documentație Hearth License Client pentru Laravel

## Instalare

```bash
composer require hearth/license-client
```

## Configurare

- Nu este necesară nicio configurare la client. Pachetul este blocat (fără `config/license-client.php`). Singurul lucru pe care îl setezi este cheia de licență (din UI/CLI).
- Pentru testare locală, poți adăuga un repository de tip `path` în `composer.json`.

## Utilizare

1. Rulează comanda:
   ```bash
   php artisan make:license-server CHEIA-TA
   ```
2. La succes, fișierul `storage/license.json` va fi creat și criptat.
3. Middleware-ul `Hearth\LicenseClient\Middleware\EnsureHasValidLicense` va bloca accesul până la validare.

## Flux licențiere (ping-pong)

- Clientul trimite cheia + domeniu la autoritate
- Autoritatea răspunde cu status (valid/pending/invalid) + semnătură
- Clientul verifică semnătura și salvează local
- Middleware-ul permite acces doar cu licență validă

## Detalii
- Poți re-verifica licența oricând (UI/CLI)
- Pending = aplicația e blocată până la aprobare
- Orice modificare manuală a fișierului de licență blochează accesul
- Autoritatea poate trimite licențe noi direct către endpoint-ul clientului
- Mesajele de la autoritate sunt afișate clar în UI

---
Vezi și: [README.md principal](../README.md)

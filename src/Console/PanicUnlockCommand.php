<?php

namespace Hearth\LicenseClient\Console;

use Illuminate\Console\Command;

class PanicUnlockCommand extends Command
{
    // TTL is intentionally not configurable by the person running the command.
    // The command will always apply the `max_unlock_ttl` from config to avoid
    // allowing arbitrary long-lived unlocks.
    protected $signature = 'license:panic {--lock : Immediately remove any unlock}';

    protected $description = 'Temporarily unlock the site (bypass license enforcement) or remove the unlock.';

    public function handle()
    {
        $path = storage_path('license_client_unlock.json');

        if ($this->option('lock')) {
            if (file_exists($path)) {
                @unlink($path);
                if (config('license-client.audit_unlocks', true)) {
                    logger()->info('license:panic lock removed', [
                        'method' => 'artisan',
                        'command' => 'license:panic --lock',
                        'actor' => get_current_user(),
                        'host' => gethostname(),
                    ]);
                }

                $this->info('Lock removed. Enforcement re-enabled.');
                return 0;
            }
            $this->info('No unlock file present.');
            return 0;
        }

        // Use the configured maximum TTL; do not allow the invoker to supply a TTL.
        $ttl = (int) config('license-client.max_unlock_ttl', 3600);
        $payload = ['expires_at' => date(DATE_ISO8601, time() + $ttl)];
        try {
            file_put_contents($path, json_encode($payload));
        } catch (\Throwable $e) {
            $this->error('Failed to create unlock: ' . $e->getMessage());
            return 1;
        }

        if (config('license-client.audit_unlocks', true)) {
            logger()->info('license:panic unlock created', [
                'method' => 'artisan',
                'command' => 'license:panic',
                'ttl_applied' => $ttl,
                'expires_at' => $payload['expires_at'],
                'actor' => get_current_user(),
                'host' => gethostname(),
            ]);
        }

        $this->info('Temporary unlock created (expires_at=' . $payload['expires_at'] . ')');
        return 0;
    }
}

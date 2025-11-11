<?php

namespace Hearth\LicenseClient;

/**
 * Simple, non-configurable package messages.
 * Keep messages here so applications cannot easily override the blocked text.
 */
class Messages
{
    protected static array $messages = [
        'not_present' => 'Pentru a rula această aplicație este necesară o licență validă. Vă rugăm să contactați suportul.',
        'invalid' => 'Licența instalată pare a fi invalidă sau coruptă. Vă rugăm să reinstalați licența.',
        'not_active' => 'Licența nu este activă. Activați licența sau contactați suportul.',
        'domain_mismatch' => 'Această licență nu este valabilă pentru acest domeniu.',
        'expired' => 'Licența dumneavoastră a expirat. Vă rugăm să reînnoiți licența pentru a continua.',
    ];

    public static function get(string $key): string
    {
        return static::$messages[$key] ?? static::$messages['invalid'];
    }
}

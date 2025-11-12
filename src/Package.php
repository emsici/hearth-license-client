<?php

namespace Hearth\LicenseClient;

/**
 * Internal, non-overridable package configuration.
 * Clients cannot change these values; only the authority can update the package.
 */
final class Package
{
    // Fixed authority and endpoints
    private const AUTHORITY_URL = 'https://hearth.master-data.ro';
    private const VERIFY_ENDPOINT = '/api/verify';
    private const PEM_ENDPOINT = '/keys/pem';
    private const ALERT_ENDPOINT = '/api/alert/fraud';

    // Networking
    private const REMOTE_TIMEOUT = 5; // seconds

    // Enforcement
    private const GLOBAL_ENFORCE = true; // always prepend to kernel too

    // Files
    private const FINGERPRINT_FILE = 'license-fingerprint.json';

    // Whitelisted paths that bypass enforcement (prefix matches)
    private const WHITELIST = [
        '/health',
        '/.well-known/push-license',
        '/.well-known/jwks.json',
        '/keys/pem',
        '/licente',
        '/licenta',
        '/setari',
    ];

    public static function authorityUrl(): string
    {
        return self::AUTHORITY_URL;
    }

    public static function verifyEndpoint(): string
    {
        return self::VERIFY_ENDPOINT;
    }

    public static function pemEndpoint(): string
    {
        return self::PEM_ENDPOINT;
    }

    public static function alertEndpoint(): string
    {
        return self::ALERT_ENDPOINT;
    }

    public static function remoteTimeout(): int
    {
        return self::REMOTE_TIMEOUT;
    }

    public static function globalEnforce(): bool
    {
        return self::GLOBAL_ENFORCE;
    }

    public static function fingerprintFile(): string
    {
        return self::FINGERPRINT_FILE;
    }

    public static function whitelist(): array
    {
        return self::WHITELIST;
    }

    /**
     * Where the authority's private key should live in an authority install.
     * Clients should never ship this file.
     */
    public static function privateKeyPath(): ?string
    {
        $candidate = storage_path('private.pem');
        return file_exists($candidate) ? $candidate : null;
    }
}

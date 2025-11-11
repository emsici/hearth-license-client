<?php

return [
	// Authority base URL used for remote manifest / jwks checks
	'authority_url' => env('LICENSE_AUTHORITY_URL', 'https://hearth.master-data.ro'),

	// Whether to enforce license checks automatically (cannot be disabled by client easily)
	'enforce' => true,

	// Whether enforcement should be applied to the global HTTP kernel (prepend
	// middleware). When true, middleware will be prepended to Kernel; when
	// false only pushed to 'web' group.
	'global_enforce' => true,

	// Whitelisted request paths (prefix matches) that bypass enforcement. Use
	// this for /health, JWKS, push endpoints, etc.
	'whitelist' => [
		'/health',
		'/.well-known/push-license',
		'/.well-known/jwks.json',
		'/keys/pem',
		// Allow the package's interactive management UI and main app settings
		'/licente',
		'/licenta',
		'/setari',
	],
	// Interval (seconds) to allow remote manifest JWKS fetch timeouts before falling back
	'remote_timeout' => 5,

	// Authority alert endpoint (relative to authority_url) to receive signed
	// notifications when a potential fraud or integrity issue is detected.
	'authority_alert_endpoint' => env('LICENSE_AUTHORITY_ALERT_ENDPOINT', '/api/alert/fraud'),

	// Verification endpoint path (relative to authority_url) used to verify
	// a pasted license key. Can be overridden via env LICENSE_VERIFY_ENDPOINT.
	'verify_endpoint' => env('LICENSE_VERIFY_ENDPOINT', '/api/verify'),

	// Public key PEM endpoint on authority (relative path)
	'pem_endpoint' => env('LICENSE_PEM_ENDPOINT', '/keys/pem'),

	// Path to write the signed fingerprint file (relative to storage_path())
	'fingerprint_file' => env('LICENSE_FINGERPRINT_FILE', 'license-fingerprint.json'),
];


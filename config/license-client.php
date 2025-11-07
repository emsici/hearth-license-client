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
	],
	// Interval (seconds) to allow remote manifest JWKS fetch timeouts before falling back
	'remote_timeout' => 5,
];


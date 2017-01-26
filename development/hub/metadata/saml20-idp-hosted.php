<?php
/**
 * SAML 2.0 IdP configuration for SimpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-hosted
 */

$metadata['ssp-hub.local'] = array(
	/*
	 * The hostname of the server (VHOST) that will use this SAML entity.
	 *
	 * Can be '__DEFAULT__', to use this entry by default.
	 */
	'host' => 'ssp-hub.local',

	// X.509 key and certificate. Relative to the cert directory.
	'privatekey' => 'saml.pem',
	'certificate' => 'saml.crt',

    // User the SSOService.php file provided by the sildisco module
    'SingleSignOnService' => 'sp-hub.local/module.php/sildisco/idp/SSOService.php',

	/*
	 * Authentication source to use. Must be one that is configured in
	 * 'config/authsources.php'.
	 */
	'auth' => 'hub-discovery',
    'authproc' => [
        95 => [
                  'class' =>'sildisco:TrackIdps',
              ]
    ],
);

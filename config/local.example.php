<?php
declare(strict_types=1);
// Sanitized template only. Copy to config/local.php on the hosting account,
// then enter your password privately. Never add local.php to Git or a package.
if (!defined('PORTAL_CONFIG_LOADING')) { http_response_code(404); exit; }

return [
    'environment' => 'production',
    'canonical_origin' => 'https://university-lost-and-found.infinityfreeapp.com',
    'database' => [
        'host' => 'sql303.infinityfree.com',
        'port' => '3306',
        'name' => 'if0_43303703_university_lost_and_found',
        'user' => 'if0_43303703',
        'password' => '', // Enter privately on the server; empty is rejected.
    ],
];

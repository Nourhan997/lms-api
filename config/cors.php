<?php

declare(strict_types=1);

return [
    'paths'                    => ['api/*'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => [env('APP_FRONTEND_URL', '*')],
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
    'max_age'                  => 0,
    'supports_credentials'     => true,
];

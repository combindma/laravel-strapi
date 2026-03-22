<?php

return [
    'graphql_url' => env('STRAPI_GRAPHQL_URL', ''),
    'token' => env('STRAPI_TOKEN', ''),
    'timeout' => (int) env('STRAPI_TIMEOUT', 30),
    'webhook_secret' => env('STRAPI_WEBHOOK_SECRET', ''),
];

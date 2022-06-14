<?php

return [
    'github_token' => env('GITHUB_TOKEN'),
    'repo_url' => env('REPO_URL'),
    'api_url' => env('API_URL'),
    'mirror_url' => env('MIRROR_URL'),
    'dist_url' => env('DIST_URL'),
    'provider_url' => env('PROVIDER_URL'),
    'build_cache' => env('BUILD_CACHE', true),
    'api_iteration_interval' => env('API_ITERATION_INTERVAL', 5),
    'user_agent' => env('USER_AGENT'),
];
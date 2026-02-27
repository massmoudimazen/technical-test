<?php

return [
    'token' => env('CAPTAIN_TOKEN', ''),
    'token_header' => env('CAPTAIN_TOKEN_HEADER', 'X-CAPTAIN-TOKEN'),
    'scrape_interval' => env('SCRAPE_INTERVAL', 5),
    'scrape_queue' => env('SCRAPE_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
    'scrape_queue_name' => env('SCRAPE_QUEUE_NAME', 'scrapers'),
    'request_timeout' => (int) env('SCRAPE_REQUEST_TIMEOUT', 15),
    'connect_timeout' => (int) env('SCRAPE_CONNECT_TIMEOUT', 5),
    'debug' => env('CAPTAIN_DEBUG', false),
];

<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api',

    'api_domain' => null,

    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => 'Syrian Sports Venue Booking Platform API — دق احجزلي',
        'contact' => [
            'name' => 'Daq Ehjezly Team',
            'email' => 'api@daqehjezly.sy',
        ],
    ],

    'ui' => [
        'title' => 'دق احجزلي API',
        'theme' => 'light',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
        'layout' => 'responsive',
    ],

    'servers' => [
        'Local Development' => 'http://localhost:8000/api',
        'Staging' => 'https://staging-api.daqehjezly.sy',
    ],

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];

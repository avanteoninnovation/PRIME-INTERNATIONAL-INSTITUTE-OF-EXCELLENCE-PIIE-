<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        /*
         * Prospective students applying through the public admissions portal.
         * Kept on its own guard (and its own table) so no role middleware in
         * app/Http/Middleware can ever resolve an applicant as a staff or
         * student user — see the create_applicants_table migration.
         */
        'applicant' => [
            'driver' => 'session',
            'provider' => 'applicants',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'applicants' => [
            'driver' => 'eloquent',
            'model' => App\Models\Applicant::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],

        'applicants' => [
            'provider' => 'applicants',
            'table' => 'applicant_password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];

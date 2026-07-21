<?php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'your_database_name',
        'user' => 'your_database_user',
        'pass' => 'your_database_password',
        'charset' => 'utf8mb4',
    ],
    'site_url' => 'https://example.com/funnel-module',
    'admin_email' => 'you@bastiontech.example',
    'cron_secret_token' => 'CHANGE_THIS_LONG_RANDOM_TOKEN',
    'batch_limit' => 25,
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'smtp_user',
        'password' => 'smtp_password',
        'encryption' => 'tls', // tls, ssl, or empty string
    ],
    'default_from_email' => 'hello@example.com',
    'default_from_name' => 'BastionTech',
];

<?php

$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '5432';
$name = getenv('DB_NAME') ?: 'simrs';
$user = getenv('DB_USER') ?: 'simrs_app';
$password = getenv('DB_PASSWORD') ?: '';

return [
    'class' => \yii\db\Connection::class,
    'dsn' => "pgsql:host={$host};port={$port};dbname={$name}",
    'username' => $user,
    'password' => $password,
    'charset' => 'UTF8',
    'schemaMap' => [
        'pgsql' => [
            'class' => \yii\db\pgsql\Schema::class,
            'defaultSchema' => 'simrs',
        ],
    ],

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];

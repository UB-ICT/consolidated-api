<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'porsql'),
    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [
        // 'pgsql' => [
        //     'driver' => 'pgsql',
        //     'url' => env('DB_URL'),
        //     'host' => env('DB_HOST', 'api-db'),
        //     'port' => env('DB_PORT', '5432'),
        //     'database' => env('PGSQL_DATABASE', 'ub'),
        //     'username' => env('PGSQL_USERNAME', 'postgres'),
        //     'password' => env('PGSQL_PASSWORD', 'password'),
        //     'charset' => env('DB_CHARSET', 'utf8'),
        //     'prefix' => '',
        //     'prefix_indexes' => true,
        //     'search_path' => 'public',
        //     'sslmode' => 'prefer',
        // ],

        'porsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'consolidated-api-db'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('PGSQL_DATABASE', 'ub'),
            'username' => env('PGSQL_USERNAME', 'postgres'),
            'password' => env('PGSQL_PASSWORD', 'password'),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        // UBPortal lives in its own PostgreSQL schema while still sharing
        // cross-module tables (for example `users`) from `public`.
        'ubportal' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'consolidated-api-db'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('PGSQL_DATABASE', 'ub'),
            'username' => env('PGSQL_USERNAME', 'postgres'),
            'password' => env('PGSQL_PASSWORD', 'password'),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // Resolve unqualified table names in `ubportal` first, then `public`.
            'search_path' => 'ubportal,public',
            'sslmode' => 'prefer',
        ],


        'firestore' => [
            'driver' => 'mongodb',
            'dsn' => env('MONGODB_URI'),
            'database' => env('MONGODB_DATABASE', 'testmongodb'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('XENEGRADE_HOST', 'xenegrade-r3-production.claff23atn4b.us-east-1.rds.amazonaws.com'),
            'port' => env('XENEGRADE_PORT', '1433'),
            'database' => env('XENEGRADE_DATABASE', 'UBelize'),
            'username' => env('XENEGRADE_USERNAME', 'UBelizeREadOnly'),
            'password' => env('XENEGRADE_PASSWORD', 'Jh7^4z9t!m2945'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'true'),
            // Uses App\Database\Connectors\SqlServerConnector (pdo_sqlsrv-safe PDO options).
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];

<?php

namespace App\Providers;

use App\Database\Connectors\SqlServerConnector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('db.connector.sqlsrv', SqlServerConnector::class);

        $ensure = base_path('scripts/ensure-storage-dirs.php');
        if (is_file($ensure)) {
            require $ensure;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ConnectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge the module config so config('connect.*') is always available.
        $this->mergeConfigFrom(
            base_path('config/connect.php'),
            'connect'
        );
    }

    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(base_path('routes/connect.php'));

        // Migrations (they also live in the standard database/migrations dir).
        $this->loadMigrationsFrom(database_path('migrations'));

        // Views are referenced as connect.admin.*, connect.business.* and resolve
        // from the standard resources/views directory, so no explicit registration
        // is required. If you namespace them, register the path here.
    }
}

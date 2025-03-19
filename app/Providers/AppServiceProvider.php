<?php

namespace App\Providers;

use App\Repositories\UserRepository;
use Ody\Foundation\Providers\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register user repository
        $this->container->singleton('user.repository', function () {
            return new UserRepository();
        });
    }

    public function boot(): void
    {
        // Boot logic
    }
}
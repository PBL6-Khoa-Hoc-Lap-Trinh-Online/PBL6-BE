<?php

namespace App\Providers;

use App\Repositories\ReceiverAddressInterface;
use App\Repositories\ReceiverAddressRepository;
use App\Repositories\UserInterface;
use App\Repositories\UserRepository;
use App\Repositories\AdminInterface;
use App\Repositories\AdminRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(AdminInterface::class, AdminRepository::class);

        $this->app->bind(ReceiverAddressInterface::class, ReceiverAddressRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

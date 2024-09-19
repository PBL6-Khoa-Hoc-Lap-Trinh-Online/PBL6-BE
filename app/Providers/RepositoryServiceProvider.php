<?php

namespace App\Providers;

use App\Repositories\ProductInterface;
use App\Repositories\ProductRepository;
use App\Repositories\ReceiverAddressInterface;
use App\Repositories\ReceiverAddressRepository;
use App\Repositories\SupplierInterface;
use App\Repositories\CategoryInterface;
use App\Repositories\UserInterface;
use App\Repositories\UserRepository;
use App\Repositories\AdminInterface;
use App\Repositories\AdminRepository;
use App\Repositories\BrandInterface;
use App\Repositories\BrandRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\CategoryRepository;
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
        $this->app->bind(BrandInterface::class, BrandRepository::class);
        $this->app->bind(SupplierInterface::class, SupplierRepository::class);
        $this->app->bind(CategoryInterface::class, CategoryRepository::class);
        $this->app->bind(ProductInterface::class, ProductRepository::class);
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

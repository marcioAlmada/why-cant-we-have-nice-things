<?php
namespace History\RequestsGatherer;

use Illuminate\Contracts\Cache\Repository;
use League\Container\ServiceProvider;

class RequestsGathererServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $provides = [
        RequestsGatherer::class,
    ];

    /**
     * Use the register method to register items with the container via the
     * protected $this->container property or the `getContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $this->container->singleton(RequestsGatherer::class, function () {
            return new RequestsGatherer($this->container->get(Repository::class));
        });
    }
}
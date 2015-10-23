<?php
namespace History;

use Dotenv\Dotenv;
use History\Providers\CacheServiceProvider;
use History\Providers\DatabaseServiceProvider;
use History\Providers\RoutingServiceProvider;
use History\Providers\TwigServiceProvider;
use History\RequestsGatherer\RequestsGatherer;
use History\RequestsGatherer\RequestsGathererServiceProvider;
use Illuminate\Database\Capsule\Manager;
use League\Container\ContainerInterface;
use League\Route\Dispatcher;
use League\Route\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

class Application
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $providers = [
        CacheServiceProvider::class,
        RequestsGathererServiceProvider::class,
        RoutingServiceProvider::class,
        TwigServiceProvider::class,
        DatabaseServiceProvider::class,
    ];

    /**
     * Application constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        // Load dotenv file
        $dotenv = new Dotenv(__DIR__.'/..');
        $dotenv->load();

        // Boot up providers
        foreach ($this->providers as $provider) {
            $this->container->addServiceProvider($provider);
        }

        // Boot database and seed it
        $this->container->get(Manager::class);
        $this->container->get(RequestsGatherer::class)->createRequests();
    }

    /**
     * Run the application.
     */
    public function run()
    {
        /** @var Dispatcher $dispatcher */
        /* @type Request $request */
        $dispatcher = $this->container->get(RouteCollection::class)->getDispatcher();
        $request    = $this->container->get(Request::class);
        $response   = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());

        return $response->send();
    }
}

<?php

/**
 * License
 * Copyright (c) 2019 Mihai Catalin Cirjaliu
 *
 * This file is part of the Modular Project
 * Redistribution and the use of the source is strictly
 * prohibided without the creator's permission
 *
 * The above copyright notice and this permission notice
 * shall be included in all copies or substantial portions
 * of the Software.
 *
 * All rights reserved
 */

namespace Modular\Module;

use Modular\Application;
use Modular\Exception\Module\InvalidInputException;
use Modular\Exception\Module\ModuleNotFoundException;
use Modular\Module\Loader\LoaderInterface;
use Modular\Router\Router;

class Manager
{
    /**
     * Application instance
     *
     * @var \Modular\Application $app
     */
    protected $app;

    /**
     * Application router
     *
     * @var \Modular\Router\Router $router
     */
    protected $router;

    /**
     * List of the modules
     *
     * @var array $modules
     */
    protected $modules = [];

    /**
     * Manager constructor.
     *
     * @param Application $app
     * @param Router $router
     */
    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    /**
     * egister a module loader and register the modules
     *
     * @param LoaderInterface $loader
     * @param $params
     *
     * @throws InvalidInputException
     */
    public function register(LoaderInterface $loader, $params)
    {
        if (is_string($loader)) {
            $loader = $this->app->make($loader, $params);
        }

        if (!$loader instanceof LoaderInterface) {
            throw new InvalidInputException('Invalid loader provided to ' . __CLASS__);
        }

        $this->registerModules($loader->get());
    }

    /**
     * Get the module if it exists
     *
     * @param $abstract
     *
     * @return mixed
     *
     * @throws ModuleNotFoundException
     */
    public function get($abstract)
    {
        if (!in_array($abstract, $this->modules)) {
            throw new ModuleNotFoundException('Module ' . $abstract . ' was not found');
        }

        return $this->modules[$abstract];
    }

    /**
     * Get the list with the modules
     *
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Register the provided modules
     *
     * @param $modules
     *
     * @return void
     */
    protected function registerModules(array $modules): void
    {
        foreach ($modules as $module) {
            if (!in_array($module, $this->modules)) {
                $this->registerRoutes($module);
                $this->registerViews($module);

                $this->modules[] = $module;
            }
        }
    }

    /**
     * Register the module routes in the Router
     *
     * @param Module $module
     */
    protected function registerRoutes(Module $module)
    {
        $routes = $module->routesPath('web.php');
        $namespace = ucfirst($module->name()) . '\\Http\\Controllers';

        if ($this->app['files']->exists($routes)) {
            $this->router->group(['namespace' => $namespace], function () use ($routes) {
                require $routes;
            });
        }
    }

    /**
     * Register the module views paths in the view finder
     *
     * @param Module $module
     */
    protected function registerViews(Module $module)
    {
        $namespace = strtolower($module->name());

        $this->app['view.finder']->addNamespace($namespace, $module->path('Resources/Views/'));
    }
}
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

namespace Modular;

use Illuminate\Container\Container;
use Modular\Http\Request;
use Modular\Module\Loader\DirectoryLoader;

class Application extends Container
{
    /**
     * Application version
     *
     * @var string $version
     */
    protected $version;

    /**
     * Application base path
     *
     * @var string $basePath
     */
    protected $basePath;

    /**
     * Application available bindings
     *
     * @var array $availableBindings
     */
    protected $availableBindings = [];

    /**
     * Application registred bindings
     *
     * @var array $registeredBindings
     */
    protected $registeredBindings = [];

    /**
     * Application constructor.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Run the application
     *
     * @param \Modular\Http\Request $request
     */
    public function run(Request $request): void
    {
        $this->instance('request', $request);

        $this->make('manager')->register(DirectoryLoader::class, [
            'path' => $this->modulesPath(),
            'filter' => DirectoryLoader::FILTER_ALL,
        ]);

        $this->make('router')->handle($request);
    }

    /**
     * Resolve the given type from the container
     *
     * @param string $abstract
     * @param array $parameters
     *
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (!$this->bound($abstract) && array_key_exists($abstract, $this->availableBindings) &&
            !array_key_exists($this->availableBindings[$abstract], $this->registeredBindings)
        ) {
            $this->{$method = $this->availableBindings[$abstract]}();
            $this->registeredBindings[$method] = true;
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * Set application locale
     *
     * @param string $locale
     * @param string $fallback
     *
     * @return void
     */
    public function setLocale(string $locale, string $fallback = 'en'): void
    {
        $this['config']->set('app.locale', $locale);
        $this['config']->set('app.fallback_locale', $fallback);

        $this['translator']->setLocale($locale);
    }

    /**
     * Register a new binding in the container
     *
     * @param string $alias
     * @param \Closure $binding
     *
     * @return void
     */
    public function registerBinding(string $alias, $binding): void
    {
        if (!array_key_exists($alias, $this->availableBindings)) {
            $this->availableBindings[$alias] = $binding;
        }
    }

    /**
     * Get the base path of the Modular installation
     *
     * @param string $path Optionally, a path to append to the base path
     *
     * @return string
     */
    public function basePath($path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the app directory
     *
     * @param string $path
     *
     * @return string
     */
    public function path($path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'app' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the config directory
     *
     * @param string $path
     *
     * @return string
     */
    public function configPath($path = ''): string
    {
        return $this->path('config') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the modules directory
     *
     * @param string $path Optionally, a path to append to the base path
     *
     * @return string
     */
    public function modulesPath($path = ''): string
    {
        return $this->path('modules') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the public / web directory
     *
     * @param string $path Optionally, a path to append to the base path
     *
     * @return string
     */
    public function publicPath($path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'public' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to storage directory
     *
     * @param string $path Optionally, a path to append to the base path
     *
     * @return string
     */
    public function storagePath($path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}
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

namespace Modular\Bootstrapper;

use Modular\Application;

class Bootstrapper
{
    use ProvidesAvailableComponents,
        ProvidesComponentsBindings;

    /**
     * Application instance
     *
     * @var \Modular\Application $app
     */
    protected $app;

    /**
     * Application components
     *
     * @var array $components
     */
    protected $components = [];

    /**
     * Bootstrapper constructor.
     *
     * @param array $components
     */
    public function __construct(array $components)
    {
        $this->components = $components;
    }

    /**
     * Bootstrap the given application
     *
     * @param \Modular\Application $app
     */
    public function bootstrap(Application $app)
    {
        $this->app = $app;

        $componentsToRegister = $this->getComponents();

        foreach ($componentsToRegister as $component => $params) {
            foreach ($params['aliases'] as $key => $aliases) {
                if (is_array($aliases)) {
                    foreach ($aliases as $alias) {
                        $app->alias($key, $alias);
                    }
                }
            }

            foreach ($params['bindings'] as $binding) {
                $method = $params['method'];
                if (method_exists($this, $method)) {
                    $app->registerBinding($binding, $this->$method());
                }
            }
        }
    }

    /**
     * Get the components array
     *
     * @return array
     */
    protected function getComponents(): array
    {
        $source = array_merge($this->requiredComponents, $this->components);
        $components = [];

        foreach ($source as $value) {
            if (isset($this->availableComponents[$value])) {
                $components[] = $this->availableComponents[$value];
            }
        }

        return $components;
    }
}

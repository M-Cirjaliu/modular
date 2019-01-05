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

namespace Modular\Http;

use Modular\Router\Traits\ProvidesConvenienceMethods;

class Controller
{
    use ProvidesConvenienceMethods;
    
    /**
     * The middleware defined on the controller.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Define a middleware on the controller.
     *
     * @param  string $middleware
     * @param  array  $options
     *
     * @return void
     */
    public function middleware($middleware, array $options = [])
    {
        $this->middleware[ $middleware ] = $options;
    }

    /**
     * Get the middleware for a given method.
     *
     * @param  string $method
     *
     * @return array
     */
    public function getMiddlewareForMethod($method)
    {
        $middleware = [];
        foreach ($this->middleware as $name => $options) {
            if (isset($options['only']) && !in_array($method, (array) $options['only'])) {
                continue;
            }
            if (isset($options['except']) && in_array($method, (array) $options['except'])) {
                continue;
            }
            $middleware[] = $name;
        }
        return $middleware;
    }
}

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

namespace Modular\Exception;

use Illuminate\Support\Arr;
use Illuminate\Filesystem\Filesystem;

use Whoops\Handler\PrettyPageHandler;

class WhoopsHandler
{
    /**
     * Create a new Whoops handler for debug mode.
     *
     * @return \Whoops\Handler\PrettyPageHandler
     */
    public function forDebug()
    {
        return tap(new PrettyPageHandler, function ($handler) {
            $handler->handleUnconditionally(true);

            $this->registerApplicationPaths($handler);
        });
    }

    /**
     * Register the application paths with the handler.
     *
     * @param  \Whoops\Handler\PrettyPageHandler $handler
     *
     * @return $this
     */
    protected function registerApplicationPaths($handler)
    {
        $handler->setApplicationPaths(
            array_flip($this->directoriesExceptVendor())
        );

        return $this;
    }

    /**
     * Get the application paths except for the "vendor" directory.
     *
     * @return array
     */
    protected function directoriesExceptVendor()
    {
        return Arr::except(
            array_flip((new Filesystem)->directories(base_path())),
            [base_path('vendor')]
        );
    }
}
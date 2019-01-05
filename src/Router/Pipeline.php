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

namespace Modular\Router;

use Closure as BaseClosure;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline as BasePipeline;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;

/**
 * This extended pipeline catches any exceptions that occur during each slice.
 *
 * The exceptions are converted to HTTP responses for proper middleware handling.
 */
class Pipeline extends BasePipeline
{
    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function carry()
    {
        return function ($stack , $pipe) {
            return function ($passable) use ($stack , $pipe) {
                try {
                    $slice = parent::carry();
                    return call_user_func($slice($stack , $pipe) , $passable);
                } catch (Exception $e) {
                    return $this->handleException($passable , $e);
                } catch (Throwable $e) {
                    return $this->handleException($passable , new FatalThrowableError($e));
                }
            };
        };
    }

    /**
     * Get the initial slice to begin the stack call.
     *
     * @param  \Closure $destination
     *
     * @return \Closure
     */
    protected function prepareDestination(BaseClosure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return call_user_func($destination , $passable);
            } catch (Exception $e) {
                return $this->handleException($passable , $e);
            } catch (Throwable $e) {
                return $this->handleException($passable , new FatalThrowableError($e));
            }
        };
    }

    /**
     * Handle the given exception
     *
     * @param            $passable
     * @param \Exception $e
     *
     * @return mixed
     * @throws \Exception
     */
    protected function handleException($passable , Exception $e)
    {
        if ( !$this->container->bound(ExceptionHandler::class) || !$passable instanceof Request ) {
            throw $e;
        }
        $handler = $this->container->make(ExceptionHandler::class);
        $handler->report($e);
        return $handler->render($passable , $e);
    }
}
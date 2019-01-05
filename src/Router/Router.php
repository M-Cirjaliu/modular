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

use Closure;
use FastRoute\Dispatcher;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modular\Application;
use Modular\Http\Request;
use Modular\Router\Closure as RoutingClosure;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Router
{
    /**
     * All routes that are named
     *
     * @var array $namedRoutes
     */
    public $namedRoutes = [];

    /**
     * Application instance
     *
     * @var \Modular\Application $app
     */
    protected $app;

    /**
     * The FastRoute dispatcher.
     *
     * @var \FastRoute\Dispatcher $dispatcher
     */
    protected $dispatcher;

    /**
     * All of the global middleware for the application.
     *
     * @var array $middleware
     */
    protected $middleware = [];

    /**
     * All of the route specific middleware short-hands.
     *
     * @var array $routeMiddleware
     */
    protected $routeMiddleware = [];

    /**
     * The route group attribute stack.
     *
     * @var array $groupStack
     */
    protected $groupStack = [];

    /**
     * All of the routes waiting to be registered.
     *
     * @var array $routes
     */
    protected $routes = [];

    /**
     * The current route being dispatched.
     *
     * @var array
     */
    protected $currentRoute;

    /**
     * The latest route added to the router
     *
     * @var string $latestRouteAdded
     */
    protected $latest;

    /**
     * The latest uri registered
     *
     * @var string $latestUri
     */
    protected $latestUri;

    /**
     * Router constructor.
     *
     * @param \Modular\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Format the uses prefix for the new group attributes.
     *
     * @param  array $new
     * @param  array $old
     *
     * @return string|null
     */
    protected static function formatUsesPrefix($new, $old)
    {
        if (isset($new['namespace'])) {
            return isset($old['namespace']) && strpos($new['namespace'], '\\') !== 0
                ? trim($old['namespace'], '\\') . '\\' . trim($new['namespace'], '\\')
                : trim($new['namespace'], '\\');
        }
        return $old['namespace'] ?? null;
    }

    /**
     * Format the prefix for the new group attributes.
     *
     * @param  array $new
     * @param  array $old
     *
     * @return string|null
     */
    protected static function formatGroupPrefix($new, $old)
    {
        $oldPrefix = $old['prefix'] ?? null;
        if (isset($new['prefix'])) {
            return trim($oldPrefix, '/') . '/' . trim($new['prefix'], '/');
        }
        return $oldPrefix;
    }

    /**
     * Run the application and send the response.
     *
     * @param  SymfonyRequest|null $request
     *
     * @return void
     */
    public function handle($request = null)
    {
        $response = $this->dispatch($request);

        if ($response instanceof SymfonyResponse) {
            $response->send();
        } else {
            echo (string) $response;
        }

        if (count($this->middleware) > 0) {
            $this->callTerminableMiddleware($response);
        }
    }

    /**
     * Dispatch the incoming request.
     *
     * @param  SymfonyRequest|null $request
     *
     * @return Response
     */
    public function dispatch($request = null)
    {
        list($method , $pathInfo) = $this->parseIncomingRequest($request);

        try {
            return $this->sendThroughPipeline($this->middleware, function () use ($method, $pathInfo) {
                if (isset($this->routes[ $method . $pathInfo ])) {
                    return $this->handleFoundRoute([ true , $this->routes[ $method . $pathInfo ]['action'] , [] ]);
                }

                return $this->handleDispatcherResponse(
                    $this->createDispatcher()->dispatch($method, $pathInfo)
                );
            });
        } catch (Exception $e) {
            return $this->prepareResponse($this->sendExceptionToHandler($e));
        } catch (Throwable $e) {
            return $this->prepareResponse($this->sendExceptionToHandler($e));
        }
    }

    /**
     * Prepare the response for sending.
     *
     * @param  mixed $response
     *
     * @return Response
     */
    public function prepareResponse($response)
    {
        $request = app(Request::class);

        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        if ($response instanceof PsrResponseInterface) {
            $response = ( new HttpFoundationFactory )->createResponse($response);
        } elseif (!$response instanceof SymfonyResponse) {
            $response = new Response($response);
        } elseif ($response instanceof BinaryFileResponse) {
            $response = $response->prepare(Request::capture());
        }

        return $response->prepare($request);
    }

    /**
     * Register a set of routes with a set of shared attributes.
     *
     * @param  array    $attributes
     * @param  \Closure $callback
     *
     * @return void
     */
    public function group(array $attributes, \Closure $callback)
    {
        if (isset($attributes['middleware']) && is_string($attributes['middleware'])) {
            $attributes['middleware'] = explode('|', $attributes['middleware']);
        }

        $this->updateGroupStack($attributes);

        call_user_func($callback, $this);
        array_pop($this->groupStack);
    }

    /**
     * Merge the given group attributes.
     *
     * @param  array $new
     * @param  array $old
     *
     * @return array
     */
    public function mergeGroup($new, $old)
    {
        $new['namespace'] = static::formatUsesPrefix($new, $old);
        $new['prefix'] = static::formatGroupPrefix($new, $old);

        if (isset($new['domain'])) {
            unset($old['domain']);
        }

        if (isset($old['as'])) {
            $new['as'] = $old['as'] . ( isset($new['as']) ? '.' . $new['as'] : '' );
        }

        if (isset($old['suffix']) && !isset($new['suffix'])) {
            $new['suffix'] = $old['suffix'];
        }

        return array_merge_recursive(Arr::except($old, [ 'namespace' , 'prefix' , 'as' , 'suffix' ]), $new);
    }

    /**
     * Add a route to the collection.
     *
     * @param  array|string $method
     * @param  string       $uri
     * @param  mixed        $action
     *
     * @return void
     */
    public function addRoute($method, $uri, $action)
    {
        $action = $this->parseAction($action);
        $attributes = null;

        if ($this->hasGroupStack()) {
            $attributes = $this->mergeWithLastGroup([]);
        }

        if (isset($attributes) && is_array($attributes)) {
            if (isset($attributes['prefix'])) {
                $uri = trim($attributes['prefix'], '/') . '/' . trim($uri, '/');
            }
            if (isset($attributes['suffix'])) {
                $uri = trim($uri, '/') . rtrim($attributes['suffix'], '/');
            }
            $action = $this->mergeGroupAttributes($action, $attributes);
        }

        $uri = '/' . trim($uri, '/');

        if (isset($action['as']) && $action['as'] != null) {
            $this->namedRoutes[ $action['as'] ] = $uri;
        }

        if (is_array($method)) {
            foreach ($method as $verb) {
                $this->routes[ $verb . $uri ] = [ 'method' => $verb , 'uri' => $uri , 'action' => $action ];
            }
        } else {
            $this->routes[ $method . $uri ] = [ 'method' => $method , 'uri' => $uri , 'action' => $action ];
        }

        $this->latest = $action;

        $this->latestUri = $uri;

        return $this;
    }

    /**
     * Set the route name
     *
     * @param string $name
     */
    public function name(string $name)
    {
        $this->latest['as'] = $name;

        array_push($this->namedRoutes, $this->latest);

        $this->namedRoutes[ $this->latest['as'] ] = $this->latestUri;
    }

    /**
     * Determine if the router currently has a group stack.
     *
     * @return bool
     */
    public function hasGroupStack()
    {
        return !empty($this->groupStack);
    }

    /**
     * Register a route with the application.
     *
     * @param  string $uri
     * @param  mixed  $action
     *
     * @return $this
     */
    public function head($uri, $action)
    {
        $this->addRoute('HEAD', $uri, $action);
    }

    /**
     * Register a route with the application.
     *
     * @param  string $uri
     * @param  mixed  $action
     *
     * @return $this
     */
    public function get($uri, $action)
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register a route with the application.
     *
     * @param  string $uri
     * @param  mixed  $action
     *
     * @return $this
     */
    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a route with the application.
     *
     * @param  string $uri
     * @param  mixed  $action
     *
     * @return $this
     */
    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a route with the application.
     *
     * @param  string $uri
     * @param  mixed  $action
     *
     * @return $this
     */
    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a route with the application.
     *
     * @param  string $uri
     * @param  mixed  $action
     *
     * @return $this
     */
    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a route with the application.
     *
     * @param  string $uri
     * @param  mixed  $action
     *
     * @return $this
     */
    public function options($uri, $action)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Get the raw routes for the application.
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Add new middleware to the application.
     *
     * @param  Closure|array $middleware
     *
     * @return $this
     */
    public function middleware($middleware)
    {
        if (!is_array($middleware)) {
            $middleware = [ $middleware ];
        }
        $this->middleware = array_unique(array_merge($this->middleware, $middleware));
        return $this;
    }

    /**
     * Define the route middleware for the application.
     *
     * @param  array $middleware
     *
     * @return $this
     */
    public function routeMiddleware(array $middleware)
    {
        $this->routeMiddleware = array_merge($this->routeMiddleware, $middleware);
        return $this;
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param  array $attributes
     *
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if (!empty($this->groupStack)) {
            $attributes = $this->mergeWithLastGroup($attributes);
        }
        $this->groupStack[] = $attributes;
    }

    /**
     * Merge the given group attributes with the last added group.
     *
     * @param  array $new
     *
     * @return array
     */
    protected function mergeWithLastGroup($new)
    {
        return $this->mergeGroup($new, end($this->groupStack));
    }

    /**
     * Parse the action into an array format.
     *
     * @param  mixed $action
     *
     * @return array
     */
    protected function parseAction($action)
    {
        if (is_string($action)) {
            return [ 'uses' => $action , 'as' => null ];
        } elseif (!is_array($action)) {
            return [ $action ];
        }
        if (isset($action['middleware']) && is_string($action['middleware'])) {
            $action['middleware'] = explode('|', $action['middleware']);
        }
        return $action;
    }

    /**
     * Merge the group attributes into the action.
     *
     * @param  array $action
     * @param  array $attributes The group attributes
     *
     * @return array
     */
    protected function mergeGroupAttributes(array $action, array $attributes)
    {
        $namespace = $attributes['namespace'] ?? null;
        $middleware = $attributes['middleware'] ?? null;
        $as = $attributes['as'] ?? null;
        return $this->mergeNamespaceGroup(
            $this->mergeMiddlewareGroup(
                $this->mergeAsGroup($action, $as),
                $middleware
            ),
            $namespace
        );
    }

    /**
     * Merge the namespace group into the action.
     *
     * @param  array  $action
     * @param  string $namespace
     *
     * @return array
     */
    protected function mergeNamespaceGroup(array $action, $namespace = null)
    {
        if (isset($namespace) && isset($action['uses'])) {
            $action['uses'] = $namespace . '\\' . $action['uses'];
        }
        return $action;
    }

    /**
     * Merge the middleware group into the action.
     *
     * @param  array $action
     * @param  array $middleware
     *
     * @return array
     */
    protected function mergeMiddlewareGroup(array $action, $middleware = null)
    {
        if (isset($middleware)) {
            if (isset($action['middleware'])) {
                $action['middleware'] = array_merge($middleware, $action['middleware']);
            } else {
                $action['middleware'] = $middleware;
            }
        }
        return $action;
    }

    /**
     * Merge the as group into the action.
     *
     * @param  array  $action
     * @param  string $as
     *
     * @return array
     */
    protected function mergeAsGroup(array $action, $as = null)
    {
        if (isset($as) && !empty($as)) {
            if (isset($action['as'])) {
                $action['as'] = $as . '.' . $action['as'];
            } else {
                $action['as'] = $as;
            }
        }
        return $action;
    }

    /**
     * Prepare the HTTP Request
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    protected function prepareRequest(SymfonyRequest $request)
    {
        if (!$request instanceof Request) {
            $request = Request::createFromBase($request);
        }
        $request->setRouteResolver(function () {
            return $this->currentRoute;
        });
    }

    /**
     * Call the terminable middleware.
     *
     * @param  mixed $response
     *
     * @return void
     */
    protected function callTerminableMiddleware($response)
    {
        if ($this->shouldSkipMiddleware()) {
            return;
        }
        $response = $this->prepareResponse($response);
        foreach ($this->middleware as $middleware) {
            if (!is_string($middleware)) {
                continue;
            }
            $instance = $this->app->make(explode(':', $middleware)[0]);
            if (method_exists($instance, 'terminate')) {
                $instance->terminate($this->app->make('request'), $response);
            }
        }
    }

    /**
     * Parse the incoming request and return the method and path info.
     *
     * @param  \Symfony\Component\HttpFoundation\Request|null $request
     *
     * @return array
     */
    protected function parseIncomingRequest($request)
    {
        $this->prepareRequest($request);

        return [ $request->getMethod() , '/' . trim($request->getPathInfo(), '/') ];
    }

    /**
     * Handle the response from the FastRoute dispatcher.
     *
     * @param  array $routeInfo
     *
     * @return mixed
     */
    protected function handleDispatcherResponse($routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException;
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException($routeInfo[1]);
            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo);
        }
    }

    /**
     * Handle a route found by the dispatcher.
     *
     * @param  array $routeInfo
     *
     * @return mixed
     */
    protected function handleFoundRoute($routeInfo)
    {
        $this->currentRoute = $routeInfo;

        $this->app['request']->setRouteResolver(function () {
            return $this->currentRoute;
        });

        $action = $routeInfo[1];

        if (isset($action['middleware'])) {
            $middleware = $this->gatherMiddlewareClassNames($action['middleware']);

            return $this->prepareResponse($this->sendThroughPipeline($middleware, function () {
                return $this->callActionOnArrayBasedRoute($this['request']->route());
            }));
        }

        return $this->prepareResponse(
            $this->callActionOnArrayBasedRoute($routeInfo)
        );
    }

    /**
     * Call the Closure on the array based route.
     *
     * @param  array $routeInfo
     *
     * @return mixed
     */
    protected function callActionOnArrayBasedRoute($routeInfo)
    {
        $action = $routeInfo[1];

        if (isset($action['uses'])) {
            return $this->prepareResponse($this->callControllerAction($routeInfo));
        }

        foreach ($action as $value) {
            if ($value instanceof Closure) {
                $closure = $value->bindTo(new RoutingClosure);
                break;
            }
        }

        try {
            return $this->prepareResponse($this->app->call($closure, $routeInfo[2]));
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Call a controller based route.
     *
     * @param  array $routeInfo
     *
     * @return mixed
     */
    protected function callControllerAction($routeInfo)
    {
        $uses = $routeInfo[1]['uses'];

        if (is_string($uses) && !Str::contains($uses, '@')) {
            $uses .= '@__invoke';
        }

        list($controller , $method) = explode('@', $uses);

        if (!method_exists($instance = $this->app->make($controller), $method)) {
            throw new NotFoundHttpException;
        }

        if ($instance instanceof Controller) {
            return $this->callController($instance, $method, $routeInfo);
        } else {
            return $this->callControllerCallable(
                [ $instance , $method ],
                $routeInfo[2]
            );
        }
    }

    /**
     * Send the request through a controller.
     *
     * @param  mixed  $instance
     * @param  string $method
     * @param  array  $routeInfo
     *
     * @return mixed
     */
    protected function callController($instance, $method, $routeInfo)
    {
        $middleware = $instance->getMiddlewareForMethod($method);

        if (count($middleware) > 0) {
            return $this->callControllerWithMiddleware(
                $instance,
                $method,
                $routeInfo,
                $middleware
            );
        } else {
            return $this->callControllerCallable(
                [ $instance , $method ],
                $routeInfo[2]
            );
        }
    }

    /**
     * Send the request through a set of controller middleware.
     *
     * @param  mixed  $instance
     * @param  string $method
     * @param  array  $routeInfo
     * @param  array  $middleware
     *
     * @return mixed
     */
    protected function callControllerWithMiddleware($instance, $method, $routeInfo, $middleware)
    {
        $middleware = $this->gatherMiddlewareClassNames($middleware);

        return $this->sendThroughPipeline($middleware, function () use ($instance, $method, $routeInfo) {
            return $this->callControllerCallable([ $instance , $method ], $routeInfo[2]);
        });
    }

    /**
     * Call a controller callable and return the response.
     *
     * @param  callable $callable
     * @param  array    $parameters
     *
     * @return \Illuminate\Http\Response
     */
    protected function callControllerCallable(callable $callable, array $parameters = [])
    {
        try {
            return $this->prepareResponse(
                $this->app->call($callable, $parameters)
            );
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Gather the full class names for the middleware short-cut string.
     *
     * @param  string $middleware
     *
     * @return array
     */
    protected function gatherMiddlewareClassNames($middleware)
    {
        $middleware = is_string($middleware) ? explode('|', $middleware) : (array) $middleware;
        return array_map(function ($name) {
            list($name , $parameters) = array_pad(explode(':', $name, 2), 2, null);
            return array_get($this->routeMiddleware, $name, $name) . ( $parameters ? ':' . $parameters : '' );
        }, $middleware);
    }

    /**
     * Send the request through the pipeline with the given callback.
     *
     * @param  array    $middleware
     * @param  \Closure $then
     *
     * @return mixed
     */
    protected function sendThroughPipeline(array $middleware, Closure $then)
    {
        if (count($middleware) > 0 && !$this->shouldSkipMiddleware()) {
            return ( new Pipeline($this->app) )
                ->send($this->app->make('request'))
                ->through($middleware)
                ->then($then);
        }
        return $then();
    }

    /**
     * Determines whether middleware should be skipped during request.
     *
     * @return bool
     */
    protected function shouldSkipMiddleware()
    {
        return $this->app->bound('middleware.disable') && $this->app->make('middleware.disable') === true;
    }

    /**
     * Create a FastRoute dispatcher instance for the application.
     *
     * @return Dispatcher
     */
    protected function createDispatcher()
    {
        return $this->dispatcher ?: \FastRoute\simpleDispatcher(function ($r) {
            $this->sortRoutes();
            foreach ($this->routes as $route) {
                $r->addRoute($route['method'], $route['uri'], $route['action']);
            }
        });
    }

    /**
     * Sort routes , static routes will have a higher priority than dynamic
     * routes in order to avoid shadowing
     *
     * This is a simple combination of uasort & strpos
     *
     * @return void
     */
    protected function sortRoutes() : void
    {
        uasort($this->routes, function ($routeA, $routeB) {
            return ( strpos($routeA['uri'], '{') < strpos($routeB['uri'], '{') ) ? -1 : 1;
        });
    }
}

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

use Illuminate\Contracts\Routing\UrlRoutable;
use Modular\Application;

class UrlGenerator
{
	/**
	 * The application instance.
	 *
	 * @var \Modular\Application
	 */
	protected $app;

	/**
	 * The forced URL root.
	 *
	 * @var string
	 */
	protected $forcedRoot;

	/**
	 * The forced schema for URLs.
	 *
	 * @var string
	 */
	protected $forceScheme;

	/**
	 * The cached URL root.
	 *
	 * @var string|null
	 */
	protected $cachedRoot;

	/**
	 * A cached copy of the URL schema for the current request.
	 *
	 * @var string|null
	 */
	protected $cachedSchema;

	/**
	 * Create a new URL redirector instance.
	 *
	 * @param  \Modular\Application $app
	 *
	 * @return void
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Get the full URL for the current request.
	 *
	 * @return string
	 */
	public function full()
	{
		return $this->app->make('request')->fullUrl();
	}

	/**
	 * Get the current URL for the request.
	 *
	 * @return string
	 */
	public function current()
	{
		return $this->to($this->app->make('request')->getPathInfo());
	}

	/**
	 * Generate a url for the application.
	 *
	 * @param  string $path
	 * @param  array  $extra
	 * @param  bool   $secure
	 *
	 * @return string
	 */
	public function to($path , $extra = [] , $secure = null)
	{
		// First we will check if the URL is already a valid URL. If it is we will not
		// try to generate a new one but will simply return the URL as is, which is
		// convenient since developers do not always have to check if it's valid.
		if ( $this->isValidUrl($path) ) {
			return $path;
		}
		$scheme = $this->getSchemeForUrl($secure);
		$extra = $this->formatParametersForUrl($extra);
		$tail = implode('/' , array_map(
				'rawurlencode' , (array) $extra)
		);
		// Once we have the scheme we will compile the "tail" by collapsing the values
		// into a single string delimited by slashes. This just makes it convenient
		// for passing the array of parameters to this URL as a list of segments.
		$root = $this->getRootUrl($scheme);
		return $this->trimUrl($root , $path , $tail);
	}

	/**
	 * Generate a secure, absolute URL to the given path.
	 *
	 * @param  string $path
	 * @param  array  $parameters
	 *
	 * @return string
	 */
	public function secure($path , $parameters = [])
	{
		return $this->to($path , $parameters , true);
	}

	/**
	 * Generate a URL to an application asset.
	 *
	 * @param  string    $path
	 * @param  bool|null $secure
	 *
	 * @return string
	 */
	public function asset($path , $secure = null)
	{
		if ( $this->isValidUrl($path) ) {
			return $path;
		}
		// Once we get the root URL, we will check to see if it contains an index.php
		// file in the paths. If it does, we will remove it since it is not needed
		// for asset paths, but only for routes to endpoints in the application.
		$root = $this->getRootUrl($this->formatScheme($secure));
		return $this->removeIndex($root) . '/' . trim($path , '/');
	}

	/**
	 * Generate a URL to an application asset from a root domain such as CDN etc.
	 *
	 * @param  string    $root
	 * @param  string    $path
	 * @param  bool|null $secure
	 *
	 * @return string
	 */
	public function assetFrom($root , $path , $secure = null)
	{
		// Once we get the root URL, we will check to see if it contains an index.php
		// file in the paths. If it does, we will remove it since it is not needed
		// for asset paths, but only for routes to endpoints in the application.
		$root = $this->getRootUrl($this->formatScheme($secure) , $root);
		return $this->removeIndex($root) . '/' . trim($path , '/');
	}

	/**
	 * Generate a URL to a secure asset.
	 *
	 * @param  string $path
	 *
	 * @return string
	 */
	public function secureAsset($path)
	{
		return $this->asset($path , true);
	}

	/**
	 * Force the schema for URLs.
	 *
	 * @param  string $schema
	 *
	 * @return void
	 * @deprecated v5.5.x
	 */
	public function forceSchema($schema)
	{
		$this->forceScheme($schema);
	}

	/**
	 * Force the schema for URLs.
	 *
	 * @param  string $schema
	 *
	 * @return void
	 */
	public function forceScheme($schema)
	{
		$this->cachedSchema = null;
		$this->forceScheme = $schema . '://';
	}

	/**
	 * Get the default scheme for a raw URL.
	 *
	 * @param  bool|null $secure
	 *
	 * @return string
	 */
	public function formatScheme($secure)
	{
		if ( !is_null($secure) ) {
			return $secure ? 'https://' : 'http://';
		}
		if ( is_null($this->cachedSchema) ) {
			$this->cachedSchema = $this->forceScheme ?: $this->app->make('request')->getScheme() . '://';
		}
		return $this->cachedSchema;
	}

	/**
	 * Get the URL to a named route.
	 *
	 * @param  string    $name
	 * @param  mixed     $parameters
	 * @param  bool|null $secure
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	public function route($name , $parameters = [] , $secure = null)
	{
		if (! isset($this->app->router->namedRoutes[$name])) {
			throw new \InvalidArgumentException("Route [{$name}] not defined.");
		}
		$uri = $this->app->router->namedRoutes[$name];
		$parameters = $this->formatParametersForUrl($parameters);
		$uri = preg_replace_callback('/\[([^\]]*)\]$/', function ($matches) use ($uri, &$parameters) {
			$uri = $this->replaceRouteParameters($matches[1], $parameters);
			return ($matches[1] == $uri) ? '' : $uri;
		}, $uri);
		$uri = $this->replaceRouteParameters($uri, $parameters);
		$uri = $this->to($uri, [], $secure);
		if (! empty($parameters)) {
			$uri .= '?'.http_build_query($parameters);
		}
		return $uri;
	}

	/**
	 * Determine if the given path is a valid URL.
	 *
	 * @param  string $path
	 *
	 * @return bool
	 */
	public function isValidUrl($path)
	{
		if ( starts_with($path , [ '#' , '//' , 'mailto:' , 'tel:' , 'http://' , 'https://' ]) ) {
			return true;
		}
		return filter_var($path , FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * Set the forced root URL.
	 *
	 * @param  string $root
	 *
	 * @return void
	 */
	public function forceRootUrl($root)
	{
		$this->forcedRoot = rtrim($root , '/');
		$this->cachedRoot = null;
	}

	/**
	 * Remove the index.php file from a path.
	 *
	 * @param  string $root
	 *
	 * @return string
	 */
	protected function removeIndex($root)
	{
		$i = 'index.php';
		return str_contains($root , $i) ? str_replace('/' . $i , '' , $root) : $root;
	}

	/**
	 * Get the scheme for a raw URL.
	 *
	 * @param  bool|null $secure
	 *
	 * @return string
	 * @deprecated v5.5.x
	 */
	protected function getScheme($secure)
	{
		return $this->formatScheme($secure);
	}

	/**
	 * Get the scheme for a raw URL.
	 *
	 * @param  bool|null $secure
	 *
	 * @return string
	 */
	protected function getSchemeForUrl($secure)
	{
		if ( is_null($secure) ) {
			if ( is_null($this->cachedSchema) ) {
				$this->cachedSchema = $this->formatScheme($secure);
			}
			return $this->cachedSchema;
		}
		return $secure ? 'https://' : 'http://';
	}

	/**
	 * Format the array of URL parameters.
	 *
	 * @param  mixed|array $parameters
	 *
	 * @return array
	 */
	protected function formatParametersForUrl($parameters)
	{
		return $this->replaceRoutableParametersForUrl($parameters);
	}

	/**
	 * Replace UrlRoutable parameters with their route parameter.
	 *
	 * @param  array $parameters
	 *
	 * @return array
	 */
	protected function replaceRoutableParametersForUrl($parameters = [])
	{
		$parameters = is_array($parameters) ? $parameters : [ $parameters ];
		foreach ( $parameters as $key => $parameter ) {
			if ( $parameter instanceof UrlRoutable ) {
				$parameters[ $key ] = $parameter->getRouteKey();
			}
		}
		return $parameters;
	}

	/**
	 * Replace the route parameters with their parameter.
	 *
	 * @param  string $route
	 * @param  array  $parameters
	 *
	 * @return string
	 */
	protected function replaceRouteParameters($route , &$parameters = [])
	{
		return preg_replace_callback('/\{(.*?)(:.*?)?(\{[0-9,]+\})?\}/' , function ($m) use (&$parameters) {
			return isset($parameters[ $m[1] ]) ? array_pull($parameters , $m[1]) : $m[0];
		} , $route);
	}

	/**
	 * Get the base URL for the request.
	 *
	 * @param  string $scheme
	 * @param  string $root
	 *
	 * @return string
	 */
	protected function getRootUrl($scheme , $root = null)
	{
		if ( is_null($root) ) {
			if ( is_null($this->cachedRoot) ) {
				$this->cachedRoot = $this->forcedRoot ?: $this->app->make('request')->root();
			}
			$root = $this->cachedRoot;
		}
		$start = starts_with($root , 'http://') ? 'http://' : 'https://';
		return preg_replace('~' . $start . '~' , $scheme , $root , 1);
	}

	/**
	 * Format the given URL segments into a single URL.
	 *
	 * @param  string $root
	 * @param  string $path
	 * @param  string $tail
	 *
	 * @return string
	 */
	protected function trimUrl($root , $path , $tail = '')
	{
		return trim($root . '/' . trim($path . '/' . $tail , '/') , '/');
	}
}
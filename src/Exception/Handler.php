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

use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Modular\Application;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Whoops\Run as Whoops;

class Handler
{
	/**
	 * Application instance
	 *
	 * @var \Yggdrasil\Application $app
	 */
	protected $app;

	/**
	 * A list of the internal exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $internalDontReport = [
		HttpException::class ,
		HttpResponseException::class ,
		TokenMismatchException::class ,
		ValidationException::class ,
	];

	/**
	 * A list of the exceptions types that should not be reported
	 *
	 * @var array $dontReport
	 */
	protected $dontReport = [];

	/**
	 * Handler constructor.
	 *
	 * @param \Yggdrasil\Application $app
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Report an exception
	 *
	 * @param \Exception $e
	 */
	public function report(Exception $e)
	{
		if ( $this->shouldntReport($e) ) {
			return;
		}

		if ( method_exists($e , 'report') ) {
			return $e->report();
		}
	}

	/**
	 * Render an exception into an HttpResponse
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Exception               $e
	 *
	 * @return \Symfony\Component\HttpFoundation\Response|void
	 */
	public function render($request , Exception $e)
	{
		$e = $this->prepareException($e);

		if ( $e instanceof HttpResponseException ) {
			return $e->getResponse();
		} else if ( $e instanceof ValidationException ) {
			return $this->convertValidationExceptionToResponse($e , $request);
		}

		return $request->expectsJson()
			? $this->prepareJsonResponse($request , $e)
			: $this->prepareResponse($request , $e);
	}

	/**
	 * Determine if the exception should be reported.
	 *
	 * @param  \Exception $e
	 *
	 * @return bool
	 */
	public function shouldReport(Exception $e)
	{
		return !$this->shouldntReport($e);
	}

	/**
	 * Determine if the exception is in the "do not report" list.
	 *
	 * @param \Exception $e
	 *
	 * @return bool
	 */
	protected function shouldntReport(Exception $e)
	{
		$dontReport = array_merge($this->dontReport , $this->internalDontReport);

		return !is_null(Arr::first($dontReport , function ($type) use ($e) {
			return $e instanceof $type;
		}));
	}

	/**
	 * Prepare exception for rendering.
	 *
	 * @param  \Exception $e
	 *
	 * @return \Exception
	 */
	protected function prepareException(Exception $e)
	{
		if ( $e instanceof ModelNotFoundException ) {
			$e = new NotFoundHttpException($e->getMessage() , $e);
		} else if ( $e instanceof AuthorizationException ) {
			$e = new AccessDeniedHttpException($e->getMessage() , $e);
		} else if ( $e instanceof TokenMismatchException ) {
			$e = new HttpException(419 , $e->getMessage() , $e);
		}

		return $e;
	}

	/**
	 * Create a response object from the given validation exception.
	 *
	 * @param  \Illuminate\Validation\ValidationException $e
	 * @param  \Illuminate\Http\Request                   $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function convertValidationExceptionToResponse(ValidationException $e , $request)
	{
		if ( $e->response ) {
			return $e->response;
		}

		return $request->expectsJson()
			? $this->invalidJson($request , $e)
			: $this->invalid($request , $e);
	}

	/**
	 * Convert a validation exception into a response.
	 *
	 * @param  \Illuminate\Http\Request                   $request
	 * @param  \Illuminate\Validation\ValidationException $exception
	 *
	 * @return \Illuminate\Http\Response
	 */
	protected function invalid($request , ValidationException $exception)
	{
		return redirect($exception->redirectTo ?? url()->previous())
			->withInput($request->except($this->dontFlash))
			->withErrors($exception->errors() , $exception->errorBag);
	}

	/**
	 * Convert a validation exception into a JSON response.
	 *
	 * @param  \Illuminate\Http\Request                   $request
	 * @param  \Illuminate\Validation\ValidationException $exception
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function invalidJson($request , ValidationException $exception)
	{
		return response()->json([
			'message' => $exception->getMessage() ,
			'errors' => $exception->errors() ,
		] , $exception->status);
	}

	/**
	 * Prepare a response for the given exception.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Exception               $e
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function prepareResponse($request , Exception $e)
	{
		if ( !$this->isHttpException($e) ) {
			return $this->toIlluminateResponse($this->convertExceptionToResponse($e) , $e);
		}

		if ( !$this->isHttpException($e) ) {
			$e = new HttpException(500 , $e->getMessage());
		}

		return $this->toIlluminateResponse(
			$this->renderHttpException($e) , $e
		);
	}

	/**
	 * Create a Symfony response for the given exception.
	 *
	 * @param  \Exception $e
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function convertExceptionToResponse(Exception $e)
	{
		return SymfonyResponse::create(
			$this->renderExceptionContent($e) ,
			$this->isHttpException($e) ? $e->getStatusCode() : 500 ,
			$this->isHttpException($e) ? $e->getHeaders() : []
		);
	}

	/**
	 * Get the response content for the given exception.
	 *
	 * @param  \Exception $e
	 *
	 * @return string
	 */
	protected function renderExceptionContent(Exception $e)
	{
		if ( config('app.debug') ) {
			return tap(new Whoops , function ($whoops) {
				$whoops->pushHandler($this->whoopsHandler());

				$whoops->writeToOutput(false);

				$whoops->allowQuit(false);
			})->handleException($e);
		}
	}

	/**
	 * Get the Whoops handler for the application.
	 *
	 * @return \Whoops\Handler\Handler
	 */
	protected function whoopsHandler()
	{
		return ( new WhoopsHandler )->forDebug();
	}

	/**
	 * Render the given HttpException.
	 *
	 * @param  \Symfony\Component\HttpKernel\Exception\HttpException $e
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function renderHttpException(HttpException $e)
	{
		$this->registerErrorViewPaths();

		if ( view()->exists($view = "errors::{$e->getStatusCode()}") ) {
			return response()->view($view , [
				'errors' => new ViewErrorBag ,
				'exception' => $e ,
			] , $e->getStatusCode() , $e->getHeaders());
		}

		return $this->convertExceptionToResponse($e);
	}

	/**
	 * Register the error template hint paths.
	 *
	 * @return void
	 */
	protected function registerErrorViewPaths()
	{
		$paths = collect(app_path('errors'));

		view()->replaceNamespace('errors' , $paths->map(function ($path) {
			return "{$path}";
		})->push(__DIR__ . '/views')->all());
	}

	/**
	 * Map the given exception into an Illuminate response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Response $response
	 * @param  \Exception                                 $e
	 *
	 * @return \Illuminate\Http\Response
	 */
	protected function toIlluminateResponse($response , Exception $e)
	{
		if ( $response instanceof SymfonyRedirectResponse ) {
			$response = new RedirectResponse(
				$response->getTargetUrl() ,
				$response->getStatusCode() ,
				$response->headers->all()
			);
		} else {
			$response = new Response(
				$response->getContent() ,
				$response->getStatusCode() ,
				$response->headers->all()
			);
		}

		return $response->withException($e);
	}

	/**
	 * Prepare a JSON response for the given exception.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Exception               $e
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function prepareJsonResponse($request , Exception $e)
	{
		return new JsonResponse(
			$this->convertExceptionToArray($e) ,
			$this->isHttpException($e) ? $e->getStatusCode() : 500 ,
			$this->isHttpException($e) ? $e->getHeaders() : [] ,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}

	/**
	 * Convert the given exception to an array.
	 *
	 * @param  \Exception $e
	 *
	 * @return array
	 */
	protected function convertExceptionToArray(Exception $e)
	{
		return config('app.debug') ? [
			'message' => $e->getMessage() ,
			'exception' => get_class($e) ,
			'file' => $e->getFile() ,
			'line' => $e->getLine() ,
			'trace' => collect($e->getTrace())->map(function ($trace) {
				return Arr::except($trace , [ 'args' ]);
			})->all() ,
		] : [
			'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error' ,
		];
	}

	/**
	 * Determine if the given exception is an HTTP exception.
	 *
	 * @param  \Exception $e
	 *
	 * @return bool
	 */
	protected function isHttpException(Exception $e)
	{
		return $e instanceof HttpExceptionInterface;
	}
}
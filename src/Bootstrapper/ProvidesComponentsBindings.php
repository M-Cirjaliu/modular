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

trait ProvidesComponentsBindings
{
    /**
     * Register the application instance to the container
     *
     * @return void
     */
    protected function registerApplicationBindings(): void
    {
        Application::setInstance($this->app);

        $this->app->instance('app', $this->app);
        $this->app->instance(Application::class, $this->app);

        $this->app->instance('path', $this->app->path());
        $this->app->instance('path.config', $this->app->configPath());
        $this->app->instance('path.modules', $this->app->modulesPath());
        $this->app->instance('path.storage', $this->app->storagePath());
        $this->app->instance('path.public', $this->app->publicPath());
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerManagerBindings(): void
    {
        $this->app->singleton('manager', \Modular\Module\Manager::class);
    }

    /**
     * Register the Router
     *
     * @return void
     */
    protected function registerRouterBindings(): void
    {
        $this->app->singleton('router', \Modular\Router\Router::class);
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerConfigBindings(): void
    {
        $config = null;

        if (!is_file($cacheFile = storage_path('framework/cache/config.php'))) {
            $cacher = new \Modular\Cacher\ConfigCacher;
            $cacher->store($cacheFile);

            $config = $cacher->retrieve();
        }

        $this->app->instance('config', $config ?: new \Illuminate\Config\Repository(require $cacheFile));
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerFilesBindings(): void
    {
        $this->app->singleton('files', function () {
            return new \Illuminate\Filesystem\Filesystem;
        });
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerUrlGeneratorBindings(): void
    {
        $this->app->singleton('url', function ($app) {
            return new \Modular\Router\UrlGenerator($app);
        });
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerViewBindings(): void
    {
        $this->app->singleton('view.engine.resolver', function ($app) {
            $resolver = new \Illuminate\View\Engines\EngineResolver;

            $resolver->register('file', function () {
                return new \Illuminate\View\Engines\FileEngine;
            });

            $resolver->register('php', function () {
                return new \Illuminate\View\Engines\PhpEngine;
            });

            $resolver->register('blade', function () use ($app) {
                return new \Illuminate\View\Engines\CompilerEngine($app['blade.compiler']);
            });

            return $resolver;
        });

        $this->app->singleton('view.finder', function ($app) {
            return new \Illuminate\View\FileViewFinder($app['files'], []);
        });

        $this->app->singleton('view', function ($app) {

            $resolver = $app['view.engine.resolver'];

            $finder = $app['view.finder'];

            $factory = new \Illuminate\View\Factory($resolver, $finder, $app['events']);

            $factory->setContainer($app);

            $factory->share('app', $app);

            return $factory;
        });

        $this->app->singleton('blade.compiler', function ($app) {
            return new \Illuminate\View\Compilers\BladeCompiler(
                $app['files'],
                $app->storagePath('framework/views')
            );
        });
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerDatabaseBindings(): void
    {
        $this->app->singleton('db.factory', function ($app) {
            return new \Illuminate\Database\Connectors\ConnectionFactory($app);
        });

        $this->app->singleton('db', function ($app) {
            return new \Illuminate\Database\DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });

        \Illuminate\Database\Eloquent\Model::setConnectionResolver($this->app['db']);

        \Illuminate\Database\Eloquent\Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerEventBindings(): void
    {
        $this->app->singleton('events', function ($app) {
            return (new \Illuminate\Events\Dispatcher($app))->setQueueResolver(function () use ($app) {
                return $this->make(Queue::class);
            });
        });
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerResponseFactoryBindings(): void
    {
        $this->app->singleton(\Illuminate\Contracts\Routing\ResponseFactory::class, function ($app) {
            return new \Illuminate\Routing\ResponseFactory($app[\Illuminate\Contracts\View\Factory::class], $app['redirect']);
        });
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerCookieBindings(): void
    {
        $this->app->singleton('cookie', function ($app) {
            $config = $app->make('config')->get('session');

            return (new \Illuminate\Cookie\CookieJar)->setDefaultPathAndDomain(
                $config['path'],
                $config['domain'],
                $config['secure'],
                $config['same_site'] ?? null
            );
        });
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerSessionBindings(): void
    {
        $this->app->singleton('session', function ($app) {
            return new \Illuminate\Session\SessionManager($app);
        });

        $this->app->singleton('session.store', function ($app) {
            return $app->make('session')->driver();
        });
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerTranslationBindings(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new \Illuminate\Translation\FileLoader($app['files'], app_path('locale'));
        });

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            $locale = $app['config']['app.locale'];

            $trans = new \Illuminate\Translation\Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    /**
     * Register application bindings
     *
     * @return void
     */
    protected function registerExceptionBindings(): void
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Modular\Exception\Handler::class
        );

        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                throw new \ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function ($e) {
            $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);

            if ($e instanceof \Error) {
                $e = new \Symfony\Component\Debug\Exception\FatalThrowableError($e);
            }

            $handler->report($e);
            $handler->render($this->app->make('request'), $e)->send();
        });

        register_shutdown_function(function () {
            $errorCodes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];

            if (defined('FATAL_ERROR')) {
                $errorCodes[] = FATAL_ERROR;
            }

            if (!is_null($error = error_get_last()) && in_array($error['type'], $errorCodes)) {
                $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);

                $e = new \Symfony\Component\Debug\Exception\FatalErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);

                if ($e instanceof Error) {
                    $e = new \Symfony\Component\Debug\Exception\FatalThrowableError($e);
                }

                $handler->report($e);
                $handler->render($this->app->make('request'), $e)->send();
            }
        });
    }
}

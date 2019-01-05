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

trait ProvidesAvailableComponents
{
    /**
     * Required components in order to run without errors
     *
     * @var array $requiredComponents
     */
    protected $requiredComponents = [
        'App',
        'Files',
        'Config',
        'Manager',
        'Request',
        'Exception',
        'Event',
        'Router',
    ];

    /**
     * Available components
     *
     * @var array $availableComponents
     */
    protected $availableComponents = [
        /*********************************************************************************************
         * App Component
         *********************************************************************************************/
        'App'          => [
            'aliases'  => [
                \Illuminate\Foundation\Application::class,
                \Illuminate\Contracts\Container\Container::class,
                \Illuminate\Contracts\Foundation\Application::class,
                \Psr\Container\ContainerInterface::class,
                \Modular\Application::class,
            ],
            'bindings' => ['app'],
            'method'   => 'registerApplicationBindings',
        ],
        /*********************************************************************************************
         * Auth Component
         *********************************************************************************************/
        'Auth'         => [
            'aliases'  => [
                'auth'                 => [
                    \Illuminate\Auth\AuthManager::class,
                    \Illuminate\Contracts\Auth\Factory::class,
                ],
                'auth.driver'          => [
                    \Illuminate\Contracts\Auth\Guard::class,
                ],
                'auth.password'        => [
                    \Illuminate\Auth\Passwords\PasswordBrokerManager::class,
                    \Illuminate\Contracts\Auth\PasswordBrokerFactory::class,
                ],
                'auth.password.broker' => [
                    \Illuminate\Auth\Passwords\PasswordBroker::class,
                    \Illuminate\Contracts\Auth\PasswordBroker::class,
                ],
            ],
            'bindings' => ['auth', 'auth.driver'],
            'method'   => 'registerAuthBindings',
        ],
        /*********************************************************************************************
         * Broadcaster Component
         *********************************************************************************************/
        'Broadcasting' => [
            'aliases'  => [],
            'bindings' => [
                'Illuminate\Contracts\Broadcasting\Broadcaster',
                'Illuminate\Contracts\Broadcasting\Factory',
            ],
            'method'   => 'registerBroadcastingBindings',
        ],
        /*********************************************************************************************
         * Bus Component
         *********************************************************************************************/
        'Bus'          => [
            'aliases'  => [],
            'bindings' => [
                'Illuminate\Contracts\Bus\Dispatcher',
            ],
            'method'   => 'registerBusBindings',
        ],
        /*********************************************************************************************
         * Cache Component
         *********************************************************************************************/
        'Cache'        => [
            'aliases'  => [
                'cache'       => [
                    \Illuminate\Cache\CacheManager::class,
                    \Illuminate\Contracts\Cache\Factory::class,
                ],
                'cache.store' => [
                    \Illuminate\Cache\Repository::class,
                    \Illuminate\Contracts\Cache\Repository::class,
                ],
            ],
            'bindings' => [
                'cache',
                'cache.store',
                'Illuminate\Contracts\Cache\Factory',
                'Illuminate\Contracts\Cache\Repository',
            ],
            'method'   => 'registerCacheBindings',
        ],
        /*********************************************************************************************
         * Cookie Component
         *********************************************************************************************/
        'Cookie'       => [
            'aliases'  => [
                'cookie' => [
                    \Illuminate\Cookie\CookieJar::class,
                    \Illuminate\Contracts\Cookie\Factory::class,
                    \Illuminate\Contracts\Cookie\QueueingFactory::class,
                ],
            ],
            'bindings' => [
                'cookie',
            ],
            'method'   => 'registerCookieBindings',
        ],
        /*********************************************************************************************
         * Config Component
         *********************************************************************************************/
        'Config'       => [
            'aliases'  => [
                'config' => [
                    \Illuminate\Config\Repository::class,
                    \Illuminate\Contracts\Config\Repository::class,
                ],
            ],
            'bindings' => [
                'config',
            ],
            'method'   => 'registerConfigBindings',
        ],
        /*********************************************************************************************
         * Database ORM Component
         *********************************************************************************************/
        'Database'     => [
            'aliases'  => [
                'db'            => [
                    \Illuminate\Database\DatabaseManager::class,
                ],
                'db.connection' => [
                    \Illuminate\Database\Connection::class,
                    \Illuminate\Database\ConnectionInterface::class,
                ],
            ],
            'bindings' => [
                'db',
                'Illuminate\Database\Eloquent\Factory',
            ],
            'method'   => 'registerDatabaseBindings',
        ],
        /*********************************************************************************************
         * Encrypter Component
         *********************************************************************************************/
        'Encrypter'    => [
            'aliases'  => [
                'encrypter' => [
                    \Illuminate\Encryption\Encrypter::class,
                    \Illuminate\Contracts\Encryption\Encrypter::class,
                ],
            ],
            'bindings' => [
                'encrypter',
                'Illuminate\Contracts\Encryption\Encrypter',
            ],
            'method'   => 'registerEncrypterBindings',
        ],
        /*********************************************************************************************
         * Event Dispatcher Component
         *********************************************************************************************/
        'Event'        => [
            'aliases'  => [
                'events' => [
                    \Illuminate\Events\Dispatcher::class,
                    \Illuminate\Contracts\Events\Dispatcher::class,
                ],
            ],
            'bindings' => [
                'events',
                'Illuminate\Contracts\Events\Dispatcher',
            ],
            'method'   => 'registerEventBindings',
        ],
        /*********************************************************************************************
         * Filesystem Component
         *********************************************************************************************/
        'Files'        => [
            'aliases'  => [
                'files' => [
                    \Illuminate\Filesystem\Filesystem::class,
                ],
            ],
            'bindings' => [
                'files',
            ],
            'method'   => 'registerFilesBindings',
        ],
        /*********************************************************************************************
         * Filesystem Manager Component
         *********************************************************************************************/
        'Filesystem'   => [
            'aliases'  => [
                'filesystem'       => [
                    \Illuminate\Filesystem\FilesystemManager::class,
                    \Illuminate\Contracts\Filesystem\Factory::class,
                ],
                'filesystem.disk'  => [
                    \Illuminate\Contracts\Filesystem\Filesystem::class,
                ],
                'filesystem.cloud' => [
                    \Illuminate\Contracts\Filesystem\Cloud::class,
                ],
            ],
            'bindings' => [
                'filesystem',
                'Illuminate\Contracts\Filesystem\Factory',
            ],
            'method'   => 'registerFilesystemBindings',
        ],
        /*********************************************************************************************
         * Hasher Component
         *********************************************************************************************/
        'Hash'         => [
            'aliases'  => [
                'hash'        => [
                    \Illuminate\Hashing\HashManager::class,
                ],
                'hash.driver' => [
                    \Illuminate\Contracts\Hashing\Hasher::class,
                ],
            ],
            'bindings' => [
                'hash',
                'Illuminate\Contracts\Hashing\Hasher',
            ],
            'method'   => 'registerHashBindings',
        ],
        /*********************************************************************************************
         * Logger Component
         *********************************************************************************************/
        'Log'          => [
            'aliases'  => [
                'log' => [
                    \Illuminate\Log\LogManager::class,
                    \Psr\Log\LoggerInterface::class,
                ],
            ],
            'bindings' => [
                'Psr\Log\LoggerInterface',
            ],
            'method'   => 'registerLogBindings',
        ],
        /*********************************************************************************************
         * Modules Manager Component
         *********************************************************************************************/
        'Manager'      => [
            'aliases'  => [

            ],
            'bindings' => [
                'manager',
            ],
            'method'   => 'registerManagerBindings',
        ],
        /*********************************************************************************************
         * Queue Component
         *********************************************************************************************/
        'Queue'        => [
            'aliases'  => [
                'queue'            => [
                    \Illuminate\Queue\QueueManager::class,
                    \Illuminate\Contracts\Queue\Factory::class,
                    \Illuminate\Contracts\Queue\Monitor::class,
                ],
                'queue.connection' => [
                    \Illuminate\Contracts\Queue\Queue::class,
                ],
                'queue.failer'     => [
                    \Illuminate\Queue\Failed\FailedJobProviderInterface::class,
                ],
            ],
            'bindings' => [
                'queue',
                'queue.connection',
                'Illuminate\Contracts\Queue\Factory',
                'Illuminate\Contracts\Queue\Queue',
            ],
            'method'   => 'registerQueueBindings',
        ],
        /*********************************************************************************************
         * Router Component
         *********************************************************************************************/
        'Router'       => [
            'aliases'  => [

            ],
            'bindings' => [
                'router',
            ],
            'method'   => 'registerRouterBindings',
        ],
        /*********************************************************************************************
         * Request Component
         *********************************************************************************************/
        'Request'      => [
            'aliases'  => [
                'request' => [
                    \Illuminate\Http\Request::class,
                    \Symfony\Component\HttpFoundation\Request::class,
                    \Modular\Http\Request::class,
                ],
            ],
            'bindings' => [
                'request',
            ],
            'method'   => 'registerRouterBindings',
        ],
        /*********************************************************************************************
         * Redirector Component
         *********************************************************************************************/
        'Redirector'   => [
            'aliases'  => [
                'redirect' => [
                    \Modular\Http\Redirector::class,
                ],
            ],
            'bindings' => [],
            'method'   => 'registerRedirectorBindings',
        ],
        /*********************************************************************************************
         * PsrRequest Component
         *********************************************************************************************/
        'PsrRequest'   => [
            'aliases'  => [],
            'bindings' => [
                'Psr\Http\Message\ServerRequestInterface',
            ],
            'method'   => 'registerPsrRequestBindings',

        ],
        /*********************************************************************************************
         * PsrResponseComponent
         *********************************************************************************************/
        'PsrResponse'  => [
            'aliases'  => [],
            'bindings' => [
                'Psr\Http\Message\ResponseInterface',
            ],
            'method'   => 'registerPsrResponseBindings',
        ],
        /*********************************************************************************************
         * Session Component
         *********************************************************************************************/
        'Session'      => [
            'aliases'  => [
                'session'       => [
                    \Illuminate\Session\SessionManager::class,
                ],
                'session.store' => [
                    \Illuminate\Session\Store::class,
                    \Illuminate\Contracts\Session\Session::class,
                ],
            ],
            'bindings' => [
                'session',
                'session.store',
            ],
            'method'   => 'registerSessionBindings',
        ],
        /*********************************************************************************************
         * Translator Component
         *********************************************************************************************/
        'Translation'  => [
            'aliases'  => [
                'translator' => [
                    \Illuminate\Translation\Translator::class,
                    \Illuminate\Contracts\Translation\Translator::class,
                ],
            ],
            'bindings' => [
                'translator',
            ],
            'method'   => 'registerTranslationBindings',
        ],
        /*********************************************************************************************
         * Url Generator Component
         *********************************************************************************************/
        'UrlGenerator' => [
            'aliases'  => [
                'url' => [
                    \Modular\Router\UrlGenerator::class,
                    \Illuminate\Contracts\Routing\UrlGenerator::class,
                ],
            ],
            'bindings' => [
                'url',
            ],
            'method'   => 'registerUrlGeneratorBindings',
        ],
        /*********************************************************************************************
         * Validator Component
         *********************************************************************************************/
        'Validator'    => [
            'aliases'  => [
                'validator' => [
                    \Illuminate\Validation\Factory::class,
                    \Illuminate\Contracts\Validation\Factory::class,
                ],
            ],
            'bindings' => [
                'validator',
                'Illuminate\Contracts\Validation\Factory',
            ],
            'method'   => 'registerValidatorBindings',
        ],

        /*********************************************************************************************
         * View Component
         *********************************************************************************************/
        'View'         => [
            'aliases'  => [
                'view'           => [
                    \Illuminate\View\Factory::class,
                    \Illuminate\Contracts\View\Factory::class,
                ],
                'blade.compiler' => [
                    \Illuminate\View\Compilers\BladeCompiler::class,
                ],
            ],
            'bindings' => [
                'view',
                'view.finder',
                'Illuminate\Contracts\View\Factory',
            ],
            'method'   => 'registerViewBindings',
        ],

        /*********************************************************************************************
         * Exception Handler Component
         *********************************************************************************************/
        'Exception'    => [
            'aliases'  => [],
            'bindings' => [
                'Illuminate\Contracts\Debug\ExceptionHandler',
            ],
            'method'   => 'registerExceptionBindings',
        ],
    ];
}

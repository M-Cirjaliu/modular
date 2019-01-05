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

namespace Modular\Module\Loader;

use Modular\Exception\Module\InvalidInputException;
use Modular\Module\Factory;
use Symfony\Component\Finder\Finder;

class DirectoryLoader implements LoaderInterface
{
    /**
     * Filters
     */
    const FILTER_ENABLED_ONLY = 1;
    const FILTER_DISABLED_ONLY = 2;
    const FILTER_ALL = 3;

    /**
     * The provided modules path
     *
     * @var string $path
     */
    protected $path;

    /**
     * The provided filter
     *
     * @var $filter
     */
    protected $filter;

    /**
     * Except the provided directories
     *
     * @var array $except
     */
    protected $except;

    /**
     * DirectoryLoader constructor.
     *
     * @param string $path
     * @param int $filter
     * @param array $except
     *
     * @throws InvalidInputException
     */
    public function __construct(string $path, $filter = self::FILTER_ALL, $except = [])
    {
        if (!is_dir($path) || $filter != static::FILTER_ALL && $filter != static::FILTER_ENABLED_ONLY && $filter != static::FILTER_DISABLED_ONLY) {
            throw new InvalidInputException('Invalid input provided for ' . __CLASS__);
        }

        $this->path = $path;
        $this->filter = $filter;
        $this->except = $except;
    }

    /**
     * Get the results
     *
     * @return array
     */
    public function get(): array
    {
        $paths = iterator_to_array(
            Finder::create()
                ->ignoreDotFiles($this->filter)
                ->directories()
                ->depth(0)
                ->in($this->path));

        return array_filter(array_map(function ($path) {

            $directory = $path->getRelativePathname();

            if (!in_array($directory, $this->except) && $this->resolveFilter($directory)) {
                return Factory::fromDirectory($directory);
            }

        }, $paths));
    }

    /**
     * Resolve the provided filter and return the result
     *
     * @param string $path
     *
     * @return bool
     */
    protected function resolveFilter(string $path): bool
    {
        $resolvers = [
            static::FILTER_ENABLED_ONLY => !is_file('.disabled'),
            static::FILTER_DISABLED_ONLY => is_file('.disabled'),
        ];

        if (array_key_exists($this->filter, $resolvers) && !$resolvers[$this->filter]) {
            return false;
        }

        return true;
    }
}
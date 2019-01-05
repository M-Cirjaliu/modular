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

namespace Modular\Cacher;

use Illuminate\Config\Repository;
use Symfony\Component\Finder\Finder;

class ConfigCacher implements CacherInterface
{
    /**
     * The configs
     *
     * @var array $configs
     */
    protected $configs;

    /**
     * Store the cache file
     *
     * @param string $cacheFile
     *
     * @return mixed|void
     */
    public function store(string $cacheFile)
    {
        $config = $this->getFreshConfiguration();

        app('files')->put($cacheFile, '<?php return ' . var_export($config, true) . ';' . PHP_EOL, true);
        
        $this->configs = require $cacheFile;
    }

    /**
     * Retrieve the cached values
     *
     * @return mixed
     */
    public function retrieve()
    {
        return new Repository($this->configs);
    }

    /**
     * Get a fresh configuration
     *
     * @return array
     */
    protected function getFreshConfiguration(): array
    {
        $configPath = realpath(config_path());

        $files = Finder::create()->files()->name('*.php')->depth(0)->in($configPath);

        $configs = [];

        foreach ($files as $file) {
            $path = $file->getRealPath();
            $key  = basename($file->getBasename(), '.php');

            $configs[$key] = require $path;
        }

        return $configs;
    }
}

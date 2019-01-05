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

namespace Modular\Module;

use Modular\Exception\Module\InvalidModuleException;

class Factory
{
    /**
     * Create a new module based on a given directory
     *
     * @param string $directory
     *
     * @return \Modular\Module\Module
     * @throws \Modular\Exception\Module\InvalidModuleException
     */
    public static function fromDirectory(string $directory): Module
    {
        $path = app()->modulesPath($directory);
        $name = ucfirst($directory);
        $configFile = $path . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . $directory . '.php';

        $config = file_exists($configFile) ? include $configFile : [];

        $config['class'] = $name . '\\' . $name;
        $config['name'] = $name;
        $config['path'] = $path;

        return static::fromParams($config);
    }

    /**
     * Create a new module based on a given array
     *
     * @param array $array
     *
     * @return \Modular\Module\Module
     * @throws \Modular\Exception\Module\InvalidModuleException
     */
    public static function fromArray(array $array): Module
    {
        if (!array_key_exists('class', $array) || $array['class'] == '') {
            throw new InvalidModuleException('Invalid module parameters provided => [' . print_r($array) . ']');
        }

        return static::fromParams($array);
    }

    /**
     * Create a new module from the provided parameters and return the object
     *
     * @param array $params
     *
     * @return Module
     *
     * @throws InvalidModuleException
     */
    protected static function fromParams(array $params): Module
    {
        if (!isset($params['version'])) {
            $params['version'] = '1.0.0';
        }

        if (!isset($params['description'])) {
            $params['description'] = 'No description';
        }

        $module = app()->make($params['class'], [
            'name' => $params['name'],
            'version' => $params['version'],
            'description' => $params['description'],
            'path' => $params['path'],
        ]);

        if (!$module instanceof Module) {
            throw new InvalidModuleException('Invalid module provided => [' . $params['class'] . ']');
        }

        return $module;
    }
}

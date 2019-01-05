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

class Module
{
	/**
	 * Module name
	 *
	 * @var string $name
	 */
	protected $name;

	/**
	 * Module description
	 *
	 * @var string $description
	 */
	protected $description;

	/**
	 * Module version
	 *
	 * @var string $version
	 */
	protected $version;

	/**
	 * Module path
	 *
	 * @var string $path
	 */
	protected $path;

	/**
	 * Module constructor.
	 *
	 * @param string $name
	 * @param string $description
	 * @param string $version
	 * @param string $path
	 */
	public function __construct(string $name , string $description , string $version , string $path)
	{
		$this->name = $name;
		$this->description = $description;
		$this->version = $version;
		$this->path = $path;
	}

	/**
	 * Get the module name
	 *
	 * @return string
	 */
	public function name() : string
	{
		return $this->name;
	}

	/**
	 * Get the module description
	 *
	 * @return string
	 */
	public function description() : string
	{
		return $this->description;
	}

	/**
	 * Get the module version
	 *
	 * @return string
	 */
	public function version() : string
	{
		return $this->version;
	}

	/**
	 * Get the base path of the Module
	 *
	 * @param string $path Optionally, a path to append to the base path
	 *
	 * @return string
	 */
	public function path($path = '') : string
	{
		return $this->path . ( $path ? DIRECTORY_SEPARATOR . $path : $path );
	}

	/**
	 * Get the path to the Routes directory
	 *
	 * @param string $path Optionally, a path to append to the base path
	 *
	 * @return string
	 */
	public function routesPath($path = '') : string
	{
		return $this->path('Http/Routes/') . ( $path ? DIRECTORY_SEPARATOR . $path : $path );
	}

	/**
	 * Get the path to the Views directory
	 *
	 * @param string $path Optionally, a path to append to the base path
	 *
	 * @return string
	 */
	public function viewsPath($path = '') : string
	{
		return $this->path('Resources/Views') . ( $path ? DIRECTORY_SEPARATOR . $path : $path );
	}

	/**
	 * Get the path to the Translations directory
	 *
	 * @param string $path Optionally, a path to append to the base path
	 *
	 * @return string
	 */
	public function translationsPath($path = '') : string
	{
		return $this->path('Resources/Translations') . ( $path ? DIRECTORY_SEPARATOR . $path : $path );
	}
}
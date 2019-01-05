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

interface CacherInterface
{
    /**
     * Store the cache file
     *
     * @param string $cacheFile
     *
     * @return mixed
     */
    public function store(string $cacheFile);

    /**
     * Get the cached values
     *
     * @return mixed
     */
    public function retrieve();
}

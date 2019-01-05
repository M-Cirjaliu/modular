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

namespace Modular\Exception\Application;

use Throwable;

class DuplicateBindingException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
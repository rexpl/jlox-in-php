<?php

declare(strict_types=1);

namespace Rexpl\Lox\Exceptions\FlowExceptions;

class LoxReturn extends \Exception
{
    /**
     * @param mixed $value
     */
    public function __construct(public mixed $value) {}
}
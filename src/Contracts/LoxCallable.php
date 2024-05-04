<?php

declare(strict_types=1);

namespace Rexpl\Lox\Contracts;

use Rexpl\Lox\Interpreter;

interface LoxCallable
{
    /**
     * @return int
     */
    public function arity(): int;

    /**
     * @param \Rexpl\Lox\Interpreter $interpreter
     * @param array $arguments
     *
     * @return mixed
     */
    public function call(Interpreter $interpreter, array $arguments): mixed;
}
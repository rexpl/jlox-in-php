<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Contracts\LoxCallable;

class LoxClass implements LoxCallable
{
    /**
     * @param string $name
     * @param array<string,\Rexpl\Lox\LoxFunction> $methods
     */
    public function __construct(public readonly string $name, protected array $methods) {}

    public function arity(): int
    {
        return 0;
    }

    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        return new LoxInstance($this);
    }

    public function getMethod(string $name): ?LoxFunction
    {
        return $this->methods[$name] ?? null;
    }
}
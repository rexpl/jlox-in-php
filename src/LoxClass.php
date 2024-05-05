<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Contracts\LoxCallable;

class LoxClass implements LoxCallable
{
    /**
     * @param string $name
     * @param \Rexpl\Lox\LoxClass|null $superClass
     * @param array<string,\Rexpl\Lox\LoxFunction> $methods
     */
    public function __construct(public readonly string $name, protected ?LoxClass $superClass, protected array $methods) {}

    public function arity(): int
    {
        return $this->getMethod('init')?->arity() ?? 0;
    }

    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        $instance = new LoxInstance($this);

        $initializer = $this->getMethod('init');
        if ($initializer !== null) {
            $initializer->isInitializer = true;
            $initializer->bind($instance)->call($interpreter, $arguments);
        }

        return $instance;
    }

    public function getMethod(string $name): ?LoxFunction
    {
        return $this->methods[$name]
            ?? $this->superClass?->getMethod($name)
            ?? null;
    }
}
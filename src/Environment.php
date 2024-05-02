<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Exceptions\RuntimeError;

class Environment
{
    /**
     * @var array
     */
    protected array $values = [];

    /**
     * @param \Rexpl\Lox\Environment|null $enclosing
     */
    public function __construct(protected ?Environment $enclosing = null) {}

    public function define(Token $name, mixed $value): void
    {
        $this->values[$name->literal] = $value;
    }

    public function get(Token $name): mixed
    {
        if (array_key_exists($name->literal, $this->values)) {
            return $this->values[$name->literal];
        }

        if ($this->enclosing !== null) {
            return $this->enclosing->get($name);
        }

        throw new RuntimeError($name, \sprintf('Access to undefined variable "%s".', $name->literal));
    }

    public function assign(Token $name, mixed $value): void
    {
        if (array_key_exists($name->literal, $this->values)) {
            $this->values[$name->literal] = $value;
            return;
        }

        if ($this->enclosing !== null) {
            $this->enclosing->assign($name, $value);
            return;
        }

        throw new RuntimeError($name, \sprintf('Cannot assign value to undefined variable "%s".', $name->literal));
    }
}
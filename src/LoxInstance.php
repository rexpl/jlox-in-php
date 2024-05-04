<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Exceptions\RuntimeError;

class LoxInstance
{
    /**
     * @var array<string,mixed>
     */
    public array $fields = [];

    /**
     * @param \Rexpl\Lox\LoxClass $loxClass
     */
    public function __construct(protected LoxClass $loxClass) {}

    public function get(Token $name): mixed
    {
        if (\array_key_exists($name->literal, $this->fields)) {
            return $this->fields[$name->literal];
        }

        return $this->loxClass->getMethod($name->literal)?->bind($this)
            ?? throw new RuntimeError($name, \sprintf('Access to undefined property "%s".', $name->literal));
    }

    public function set(Token $name, mixed $value): void
    {
        $this->fields[$name->literal] = $value;
    }
}
<?php

declare(strict_types=1);

namespace Rexpl\Lox;

class Token
{
    /**
     * @param \Rexpl\Lox\TokenType $type
     * @param string $lexeme
     * @param mixed $literal
     * @param int $line
     */
    public function __construct(public TokenType $type, public string $lexeme, public mixed $literal, public int $line) {}

    public function __toString(): string
    {
        return \sprintf('"%s" "%s" "%s"', $this->type->name, $this->lexeme, $this->literal);
    }
}
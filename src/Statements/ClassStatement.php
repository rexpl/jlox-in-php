<?php

declare(strict_types=1);

namespace Rexpl\Lox\Statements;

use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class ClassStatement implements Statement
{
    /**
     * @param \Rexpl\Lox\Token $name
     * @param array<\Rexpl\Lox\Statements\FunctionStatement> $methods
     */
    public function __construct(public Token $name, public array $methods) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitClassStatement($this);
    }
}
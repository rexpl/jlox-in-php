<?php

declare(strict_types=1);

namespace Rexpl\Lox\Statements;

use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class FunctionStatement implements Statement
{
    /**
     * @param \Rexpl\Lox\Token $name
     * @param array<\Rexpl\Lox\Token> $params
     * @param \Rexpl\Lox\Statements\BlockStatement $body
     */
    public function __construct(public Token $name, public array $params, public BlockStatement $body) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitFunctionStatement($this);
    }
}
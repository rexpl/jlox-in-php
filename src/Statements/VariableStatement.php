<?php

declare(strict_types=1);

namespace Rexpl\Lox\Statements;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class VariableStatement implements Statement
{
    /**
     * @param \Rexpl\Lox\Token $name
     * @param \Rexpl\Lox\Contracts\Expression $expression
     */
    public function __construct(public Token $name, public Expression $expression) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitVariableStatement($this);
    }
}
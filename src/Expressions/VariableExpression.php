<?php

declare(strict_types=1);

namespace Rexpl\Lox\Expressions;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class VariableExpression implements Expression
{
    /**
     * @param \Rexpl\Lox\Token $name
     */
    public function __construct(public Token $name) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitVariableExpression($this);
    }
}
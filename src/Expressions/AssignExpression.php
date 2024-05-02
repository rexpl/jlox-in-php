<?php

declare(strict_types=1);

namespace Rexpl\Lox\Expressions;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class AssignExpression implements Expression
{
    /**
     * @param \Rexpl\Lox\Token $name
     * @param \Rexpl\Lox\Contracts\Expression $expression
     */
    public function __construct(public Token $name, public Expression $expression) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitAssignExpression($this);
    }
}
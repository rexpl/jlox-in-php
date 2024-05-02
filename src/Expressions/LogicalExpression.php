<?php

declare(strict_types=1);

namespace Rexpl\Lox\Expressions;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class LogicalExpression implements Expression
{
    /**
     * @param \Rexpl\Lox\Contracts\Expression $left
     * @param \Rexpl\Lox\Token $operator
     * @param \Rexpl\Lox\Contracts\Expression $right
     */
    public function __construct(public Expression $left, public Token $operator, public Expression $right) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitLogicalExpression($this);
    }
}
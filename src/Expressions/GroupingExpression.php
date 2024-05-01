<?php

declare(strict_types=1);

namespace Rexpl\Lox\Expressions;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;

class GroupingExpression implements Expression
{
    /**
     * @param \Rexpl\Lox\Contracts\Expression $expression
     */
    public function __construct(public Expression $expression) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitGroupingExpression($this);
    }
}
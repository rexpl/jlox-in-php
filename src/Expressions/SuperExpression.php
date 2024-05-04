<?php

declare(strict_types=1);

namespace Rexpl\Lox\Expressions;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class SuperExpression implements Expression
{
    /**
     * @param \Rexpl\Lox\Token $keyword
     * @param \Rexpl\Lox\Token $method
     */
    public function __construct(public Token $keyword, public Token $method) {}

    public function acceptVisitor(Visitor $visitor)
    {
        $visitor->visitSuperExpression($this);
    }
}
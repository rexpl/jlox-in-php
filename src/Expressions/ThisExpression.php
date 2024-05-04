<?php

declare(strict_types=1);

namespace Rexpl\Lox\Expressions;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class ThisExpression implements Expression
{
    /**
     * @param \Rexpl\Lox\Token $keyword
     */
    public function __construct(public Token $keyword) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitThisExpression($this);
    }
}
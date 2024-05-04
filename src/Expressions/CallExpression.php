<?php

declare(strict_types=1);

namespace Rexpl\Lox\Expressions;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class CallExpression implements Expression
{
    /**
     * @param \Rexpl\Lox\Contracts\Expression $callee
     * @param \Rexpl\Lox\Token $parentheses
     * @param array<\Rexpl\Lox\Contracts\Expression> $arguments
     */
    public function __construct(public Expression $callee, public Token $parentheses, public array $arguments) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitCallExpression($this);
    }
}
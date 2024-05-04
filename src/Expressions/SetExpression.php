<?php

declare(strict_types=1);

namespace Rexpl\Lox\Expressions;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class SetExpression implements Expression
{
    /**
     * @param \Rexpl\Lox\Contracts\Expression $object
     * @param \Rexpl\Lox\Token $name
     * @param \Rexpl\Lox\Contracts\Expression $value
     */
    public function __construct(public Expression $object, public Token $name, public Expression $value) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitSetExpression($this);
    }
}
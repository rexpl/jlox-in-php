<?php

declare(strict_types=1);

namespace Rexpl\Lox\Expressions;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;

class LiteralExpression implements Expression
{
    /**
     * @param mixed $value
     */
    public function __construct(public mixed $value) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitLiteralExpression($this);
    }
}
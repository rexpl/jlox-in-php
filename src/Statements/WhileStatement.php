<?php

declare(strict_types=1);

namespace Rexpl\Lox\Statements;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;

class WhileStatement implements Statement
{
    /**
     * @param \Rexpl\Lox\Contracts\Expression $condition
     * @param \Rexpl\Lox\Contracts\Statement $body
     */
    public function __construct(public Expression $condition, public Statement $body) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitWhileStatement($this);
    }
}
<?php

declare(strict_types=1);

namespace Rexpl\Lox\Statements;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Token;

class ReturnStatement implements Statement
{
    /**
     * @param \Rexpl\Lox\Token $keyword
     * @param \Rexpl\Lox\Contracts\Expression $value
     */
    public function __construct(public Token $keyword, public Expression $value) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitReturnStatement($this);
    }
}
<?php

declare(strict_types=1);

namespace Rexpl\Lox\Statements;

use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;

class BlockStatement implements Statement
{
    /**
     * @param array<\Rexpl\Lox\Contracts\Statement> $statements
     */
    public function __construct(public array $statements) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitBlockStatement($this);
    }
}
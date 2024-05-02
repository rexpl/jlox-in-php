<?php

declare(strict_types=1);

namespace Rexpl\Lox\Statements;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;

class IfStatement implements Statement
{
    /**
     * @param \Rexpl\Lox\Contracts\Expression $condition
     * @param \Rexpl\Lox\Contracts\Statement $thenBranch
     * @param \Rexpl\Lox\Contracts\Statement|null $elseBranch
     */
    public function __construct(public Expression $condition, public Statement $thenBranch, public ?Statement $elseBranch) {}

    public function acceptVisitor(Visitor $visitor)
    {
        return $visitor->visitIfStatement($this);
    }
}
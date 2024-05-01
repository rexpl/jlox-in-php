<?php

declare(strict_types=1);

namespace Rexpl\Lox\Contracts;

interface Expression
{
    public function acceptVisitor(Visitor $visitor);
}
<?php

declare(strict_types=1);

namespace Rexpl\Lox\Contracts;

interface Statement
{
    public function acceptVisitor(Visitor $visitor);
}
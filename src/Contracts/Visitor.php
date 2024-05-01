<?php

declare(strict_types=1);

namespace Rexpl\Lox\Contracts;

use Rexpl\Lox\Expressions\BinaryExpression;
use Rexpl\Lox\Expressions\GroupingExpression;
use Rexpl\Lox\Expressions\LiteralExpression;
use Rexpl\Lox\Expressions\UnaryExpression;

interface Visitor
{
    public function visitBinaryExpression(BinaryExpression $expression);

    public function visitGroupingExpression(GroupingExpression $expression);

    public function visitLiteralExpression(LiteralExpression $expression);

    public function visitUnaryExpression(UnaryExpression $expression);
}
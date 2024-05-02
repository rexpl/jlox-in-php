<?php

declare(strict_types=1);

namespace Rexpl\Lox\Contracts;

use Rexpl\Lox\Expressions\AssignExpression;
use Rexpl\Lox\Expressions\BinaryExpression;
use Rexpl\Lox\Expressions\GroupingExpression;
use Rexpl\Lox\Expressions\LiteralExpression;
use Rexpl\Lox\Expressions\UnaryExpression;
use Rexpl\Lox\Expressions\VariableExpression;
use Rexpl\Lox\Statements\BlockStatement;
use Rexpl\Lox\Statements\ExpressionStatement;
use Rexpl\Lox\Statements\PrintStatement;
use Rexpl\Lox\Statements\VariableStatement;

interface Visitor
{
    public function visitAssignExpression(AssignExpression $expression);

    public function visitBinaryExpression(BinaryExpression $expression);

    public function visitGroupingExpression(GroupingExpression $expression);

    public function visitLiteralExpression(LiteralExpression $expression);

    public function visitUnaryExpression(UnaryExpression $expression);

    public function visitVariableExpression(VariableExpression $expression);

    public function visitBlockStatement(BlockStatement $statement);

    public function visitExpressionStatement(ExpressionStatement $statement);

    public function visitPrintStatement(PrintStatement $statement);

    public function visitVariableStatement(VariableStatement $statement);
}
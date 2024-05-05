<?php

declare(strict_types=1);

namespace Rexpl\Lox\Contracts;

use Rexpl\Lox\Expressions\AssignExpression;
use Rexpl\Lox\Expressions\BinaryExpression;
use Rexpl\Lox\Expressions\CallExpression;
use Rexpl\Lox\Expressions\GetExpression;
use Rexpl\Lox\Expressions\GroupingExpression;
use Rexpl\Lox\Expressions\LiteralExpression;
use Rexpl\Lox\Expressions\LogicalExpression;
use Rexpl\Lox\Expressions\SetExpression;
use Rexpl\Lox\Expressions\SuperExpression;
use Rexpl\Lox\Expressions\ThisExpression;
use Rexpl\Lox\Expressions\UnaryExpression;
use Rexpl\Lox\Expressions\VariableExpression;
use Rexpl\Lox\Statements\BlockStatement;
use Rexpl\Lox\Statements\ClassStatement;
use Rexpl\Lox\Statements\ExpressionStatement;
use Rexpl\Lox\Statements\FunctionStatement;
use Rexpl\Lox\Statements\IfStatement;
use Rexpl\Lox\Statements\PrintStatement;
use Rexpl\Lox\Statements\ReturnStatement;
use Rexpl\Lox\Statements\VariableStatement;
use Rexpl\Lox\Statements\WhileStatement;

interface Visitor
{
    public function visitAssignExpression(AssignExpression $expression);

    public function visitBinaryExpression(BinaryExpression $expression);

    public function visitCallExpression(CallExpression $expression);

    public function visitGetExpression(GetExpression $expression);

    public function visitGroupingExpression(GroupingExpression $expression);

    public function visitLiteralExpression(LiteralExpression $expression);

    public function visitLogicalExpression(LogicalExpression $expression);

    public function visitSetExpression(SetExpression $expression);

    public function visitSuperExpression(SuperExpression $expression);

    public function visitThisExpression(ThisExpression $expression);

    public function visitUnaryExpression(UnaryExpression $expression);

    public function visitVariableExpression(VariableExpression $expression);

    public function visitBlockStatement(BlockStatement $statement);

    public function visitClassStatement(ClassStatement $statement);

    public function visitExpressionStatement(ExpressionStatement $statement);

    public function visitFunctionStatement(FunctionStatement $statement);

    public function visitIfStatement(IfStatement $statement);

    public function visitPrintStatement(PrintStatement $statement);

    public function visitReturnStatement(ReturnStatement $statement);

    public function visitVariableStatement(VariableStatement $statement);

    public function visitWhileStatement(WhileStatement $statement);
}
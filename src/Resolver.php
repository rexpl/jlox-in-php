<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Expressions\AssignExpression;
use Rexpl\Lox\Expressions\BinaryExpression;
use Rexpl\Lox\Expressions\CallExpression;
use Rexpl\Lox\Expressions\GroupingExpression;
use Rexpl\Lox\Expressions\LiteralExpression;
use Rexpl\Lox\Expressions\LogicalExpression;
use Rexpl\Lox\Expressions\UnaryExpression;
use Rexpl\Lox\Expressions\VariableExpression;
use Rexpl\Lox\Statements\BlockStatement;
use Rexpl\Lox\Statements\ExpressionStatement;
use Rexpl\Lox\Statements\FunctionStatement;
use Rexpl\Lox\Statements\IfStatement;
use Rexpl\Lox\Statements\PrintStatement;
use Rexpl\Lox\Statements\ReturnStatement;
use Rexpl\Lox\Statements\VariableStatement;
use Rexpl\Lox\Statements\WhileStatement;

class Resolver implements Visitor
{
    /**
     * @var \SplStack<\stdClass>
     */
    protected \SplStack $scopes;

    /**
     * @var \Rexpl\Lox\FunctionType
     */
    protected FunctionType $currentFunction = FunctionType::None;

    /**
     * @param \Rexpl\Lox\Interpreter $interpreter
     */
    public function __construct(protected Interpreter $interpreter)
    {
        $this->scopes = new \SplStack();
    }

    /**
     * @param array<\Rexpl\Lox\Contracts\Statement> $statements
     */
    public function resolve(array $statements): void
    {
        foreach ($statements as $statement) {
            $this->resolveStatement($statement);
        }
    }

    protected function resolveStatement(Statement $statement): void
    {
        $statement->acceptVisitor($this);
    }

    protected function resolveExpression(Expression $expression): void
    {
        $expression->acceptVisitor($this);
    }

    protected function beginScope(): void
    {
        $this->scopes->push(new \stdClass());
    }

    protected function endScope(): void
    {
        $this->scopes->pop();
    }

    protected function declare(Token $name): void
    {
        if ($this->scopes->isEmpty()) {
            return;
        }

        $scope = $this->scopes->top();

        if (\property_exists($scope, $name->literal)) {
            Lox::error($name->line, 'Already a variable with this name in this scope.');
        }

        $scope->{$name->literal} = false;
    }

    protected function define(Token $name): void
    {
        if ($this->scopes->isEmpty()) {
            return;
        }

        $this->scopes->top()->{$name->literal} = true;
    }

    protected function resolveLocal(Expression $expression, Token $name): void
    {
        for ($i = $this->scopes->count() - 1; $i >= 0; $i--) {
            if (isset($this->scopes->offsetGet($i)->{$name->literal})) {
                $this->interpreter->resolve($expression, $this->scopes->count() - 1 - $i);
                return;
            }
        }
    }

    protected function resolveFunction(FunctionStatement $statement, FunctionType $type): void
    {
        $enclosingFunction = $this->currentFunction;
        $this->currentFunction = $type;

        $this->beginScope();

        foreach ($statement->params as $param) {
            $this->declare($param);
            $this->define($param);
        }

        $this->resolveStatement($statement->body);
        $this->endScope();

        $this->currentFunction = $enclosingFunction;
    }

    public function visitAssignExpression(AssignExpression $expression)
    {
        $this->resolveExpression($expression->expression);
        $this->resolveLocal($expression, $expression->name);
    }

    public function visitBinaryExpression(BinaryExpression $expression)
    {
        $this->resolveExpression($expression->left);
        $this->resolveExpression($expression->right);
    }

    public function visitCallExpression(CallExpression $expression)
    {
        $this->resolveExpression($expression->callee);

        foreach ($expression->arguments as $argument) {
            $this->resolveExpression($argument);
        }
    }

    public function visitGroupingExpression(GroupingExpression $expression)
    {
        $this->resolveExpression($expression->expression);
    }

    public function visitLiteralExpression(LiteralExpression $expression)
    {
    }

    public function visitLogicalExpression(LogicalExpression $expression)
    {
        $this->resolveExpression($expression->left);
        $this->resolveExpression($expression->right);
    }

    public function visitUnaryExpression(UnaryExpression $expression)
    {
        $this->resolveExpression($expression->right);
    }

    public function visitVariableExpression(VariableExpression $expression)
    {
        $this->resolveLocal($expression, $expression->name);
    }

    public function visitBlockStatement(BlockStatement $statement)
    {
        $this->beginScope();
        $this->resolve($statement->statements);
        $this->endScope();
    }

    public function visitExpressionStatement(ExpressionStatement $statement)
    {
        $this->resolveExpression($statement->expression);
    }

    public function visitFunctionStatement(FunctionStatement $statement)
    {
        $this->declare($statement->name);
        $this->define($statement->name);

        $this->resolveFunction($statement, FunctionType::Function);
    }

    public function visitIfStatement(IfStatement $statement)
    {
        $this->resolveExpression($statement->condition);
        $this->resolveStatement($statement->thenBranch);

        if ($statement->elseBranch !== null) {
            $this->resolveStatement($statement->elseBranch);
        }
    }

    public function visitPrintStatement(PrintStatement $statement)
    {
        $this->resolveExpression($statement->expression);
    }

    public function visitReturnStatement(ReturnStatement $statement)
    {
        if ($this->currentFunction === FunctionType::None) {
            Lox::error($statement->keyword->line, 'Can\'t return from top-level code.');
        }

        $this->resolveExpression($statement->value);
    }

    public function visitVariableStatement(VariableStatement $statement)
    {
        $this->declare($statement->name);
        $this->resolveExpression($statement->expression);
        $this->define($statement->name);
    }

    public function visitWhileStatement(WhileStatement $statement)
    {
        $this->resolveExpression($statement->condition);
        $this->resolveStatement($statement->body);
    }
}
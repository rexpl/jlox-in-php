<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;
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

class Resolver implements Visitor
{
    /**
     * @var \SplStack<\stdClass>
     */
    protected \SplStack $scopes;

    /**
     * @var \stdClass
     */
    protected \stdClass $currentScope;

    /**
     * @var \Rexpl\Lox\FunctionType
     */
    protected FunctionType $currentFunction = FunctionType::None;

    /**
     * @var \Rexpl\Lox\ClassType
     */
    protected ClassType $currentClass = ClassType::None;

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
        $scope = new \stdClass();

        $this->currentScope = $scope;
        $this->scopes->push($scope);
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

        if (\property_exists($this->currentScope, $name->literal)) {
            Lox::error($name->line, 'Already a variable with this name in this scope.');
        }

        $this->currentScope->{$name->literal} = false;
    }

    protected function define(Token $name): void
    {
        if ($this->scopes->isEmpty()) {
            return;
        }

        $this->currentScope->{$name->literal} = true;
    }

    protected function resolveLocal(Expression $expression, Token $name): void
    {
        $i = 0;

        foreach ($this->scopes as $scope) {

            if (property_exists($scope, $name->literal)) {
                $depth = $i;
            }

            $i++;
        }

        if (isset($depth)) {
            $this->interpreter->resolve($expression, $depth);
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

    public function visitGetExpression(GetExpression $expression)
    {
        $this->resolveExpression($expression->object);
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

    public function visitSetExpression(SetExpression $expression)
    {
        $this->resolveExpression($expression->value);
        $this->resolveExpression($expression->object);
    }

    public function visitSuperExpression(SuperExpression $expression)
    {
        if ($this->currentClass !== ClassType::SubClass) {
            $message = $this->currentClass === ClassType::None
                ? 'Can\'t use "super" outside of a class.'
                : 'Can\'t use "super" in a class with no subclass.';
            Lox::error($expression->keyword->line, $message);
        }

        $this->resolveLocal($expression, $expression->keyword);
    }

    public function visitThisExpression(ThisExpression $expression)
    {
        if ($this->currentClass === ClassType::None) {
            Lox::error($expression->keyword->line, 'Can\'t use "this" outside of a class.');
        }

        $this->resolveLocal($expression, $expression->keyword);
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

    public function visitClassStatement(ClassStatement $statement)
    {
        $enclosingClass = $this->currentClass;
        $this->currentClass = ClassType::T_Class;

        $this->declare($statement->name);
        $this->define($statement->name);

        if ($statement->superClass !== null) {

            if ($statement->superClass->name->literal === $statement->name->literal) {
                Lox::error($statement->name->line, 'A class can\'t inherit itself.');
            }

            $this->currentClass = ClassType::SubClass;

            $this->resolveExpression($statement->superClass);

            $this->beginScope();
            $this->currentScope->{'super'} = true;
        }

        $this->beginScope();
        $this->currentScope->{'this'} = true;

        foreach ($statement->methods as $method) {
            $this->resolveFunction(
                $method,
                $method->name->literal === 'init' ? FunctionType::Initializer : FunctionType::Method
            );
        }

        $this->endScope();

        if ($statement->superClass !== null) {
            $this->endScope();
        }

        $this->currentClass = $enclosingClass;
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

        if ($statement->value === null) {
            return;
        }

        if ($this->currentFunction === FunctionType::None) {
            Lox::error($statement->keyword->line, 'Can\'t return a value from an initializer.');
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
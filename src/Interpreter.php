<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Statement;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Exceptions\RuntimeError;
use Rexpl\Lox\Expressions\AssignExpression;
use Rexpl\Lox\Expressions\BinaryExpression;
use Rexpl\Lox\Expressions\GroupingExpression;
use Rexpl\Lox\Expressions\LiteralExpression;
use Rexpl\Lox\Expressions\LogicalExpression;
use Rexpl\Lox\Expressions\UnaryExpression;
use Rexpl\Lox\Expressions\VariableExpression;
use Rexpl\Lox\Statements\BlockStatement;
use Rexpl\Lox\Statements\ExpressionStatement;
use Rexpl\Lox\Statements\IfStatement;
use Rexpl\Lox\Statements\PrintStatement;
use Rexpl\Lox\Statements\VariableStatement;
use Rexpl\Lox\Statements\WhileStatement;

class Interpreter implements Visitor
{
    protected Environment $environment;

    public function __construct()
    {
        $this->environment = new Environment();
    }

    /**
     * @param array<\Rexpl\Lox\Contracts\Statement> $statements
     */
    public function interpret(array $statements): void
    {
        try {
            foreach ($statements as $statement) {
                $this->execute($statement);
            }
        } catch (RuntimeError $e) {
            Lox::runtimeError($e);
        }
    }

    /**
     * @param array<\Rexpl\Lox\Contracts\Statement> $statements
     * @param \Rexpl\Lox\Environment $environment
     */
    public function executeBlock(array $statements, Environment $environment): void
    {
        $previous = $this->environment;

        try {
            $this->environment = $environment;

            foreach ($statements as $statement) {
                $this->execute($statement);
            }
        } finally {
            $this->environment = $previous;
        }
    }

    protected function execute(Statement $statement): void
    {
        $statement->acceptVisitor($this);
    }

    protected function evaluate(Expression $expression): mixed
    {
        return $expression->acceptVisitor($this);
    }

    public function visitAssignExpression(AssignExpression $expression)
    {
        $value = $this->evaluate($expression->expression);
        $this->environment->assign($expression->name, $value);
        return $value;
    }

    public function visitBinaryExpression(BinaryExpression $expression)
    {
        $left = $this->evaluate($expression->left);
        $right = $this->evaluate($expression->right);

        switch ($expression->token->type) {
            case TokenType::MINUS:
                $this->checkNumberOperands($expression->token, $left, $right);
                return $left - $right;
            case TokenType::PLUS:

                if (\is_float($left) && \is_float($right)) {
                    return $left + $right;
                }

                if (is_string($left) && \is_string($right)) {
                    return $left . $right;
                }

                throw new RuntimeError($expression->token, 'Operands must be two numbers or two strings.');

            case TokenType::SLASH:
                $this->checkNumberOperands($expression->token, $left, $right);
                return $left / $right;
            case TokenType::STAR:
                $this->checkNumberOperands($expression->token, $left, $right);
                return $left * $right;
            case TokenType::GREATER:
                $this->checkNumberOperands($expression->token, $left, $right);
                return $left > $right;
            case TokenType::GREATER_EQUAL:
                $this->checkNumberOperands($expression->token, $left, $right);
                return $left >= $right;
            case TokenType::LESS:
                $this->checkNumberOperands($expression->token, $left, $right);
                return $left < $right;
            case TokenType::LESS_EQUAL:
                $this->checkNumberOperands($expression->token, $left, $right);
                return $left <= $right;
            case TokenType::BANG_EQUAL:
                return ! $this->isEqual($left, $right);
            case TokenType::EQUAL_EQUAL:
                return $this->isEqual($left, $right);
        }
    }

    public function visitGroupingExpression(GroupingExpression $expression)
    {
        return $this->evaluate($expression->expression);
    }

    public function visitLiteralExpression(LiteralExpression $expression)
    {
        return $expression->value;
    }

    public function visitLogicalExpression(LogicalExpression $expression)
    {
        $left = $this->evaluate($expression->left);

        if ($expression->operator->type === TokenType::OR) {
            if ($this->isTruthy($left)) {
                return $left;
            }
        } else {
            if (!$this->isTruthy($left)) {
                return $left;
            }
        }

        return $this->evaluate($expression->right);
    }

    public function visitUnaryExpression(UnaryExpression $expression)
    {
        $value = $this->evaluate($expression->right);

        if ($expression->operator->type === TokenType::MINUS) {
            $this->checkNumberOperand($expression->operator, $value);
            return -  $value;
        }

        if ($expression->operator->type === TokenType::BANG) {
            return !$this->isTruthy($value);
        }
    }

    public function visitVariableExpression(VariableExpression $expression)
    {
        return $this->environment->get($expression->name);
    }

    public function visitBlockStatement(BlockStatement $statement)
    {
        $this->executeBlock($statement->statements, new Environment($this->environment));
    }

    public function visitExpressionStatement(ExpressionStatement $statement)
    {
        $this->evaluate($statement->expression);
    }

    public function visitIfStatement(IfStatement $statement)
    {
        $result = $this->isTruthy($this->evaluate($statement->condition));

        if ($result) {
            $this->execute($statement->thenBranch);
        } elseif ($statement->elseBranch !== null) {
            $this->execute($statement->elseBranch);
        }
    }

    public function visitPrintStatement(PrintStatement $statement)
    {
        \dump($this->evaluate($statement->expression));
    }

    public function visitVariableStatement(VariableStatement $statement)
    {
        $value = $this->evaluate($statement->expression);
        $this->environment->define($statement->name, $value);
    }

    public function visitWhileStatement(WhileStatement $statement)
    {
        while ($this->isTruthy($this->evaluate($statement->condition))) {
            $this->execute($statement->body);
        }
    }

    protected function checkNumberOperand(Token $operator, mixed $operand): void
    {
        if (\is_float($operand)) {
            return;
        }

        throw new RuntimeError($operator, 'Operand must be a number.');
    }

    protected function checkNumberOperands(Token $operator, mixed $left, mixed $right): void
    {
        if (\is_float($left) && \is_float($right)) {
            return;
        }

        throw new RuntimeError($operator, 'Operands must be numbers.');
    }

    protected function isTruthy(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (\is_bool($value)) {
            return $value;
        }

        return true;
    }

    protected function isEqual(mixed $first, mixed $second): bool
    {
        return $first === $second;
    }
}
<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Visitor;
use Rexpl\Lox\Exceptions\RuntimeError;
use Rexpl\Lox\Expressions\BinaryExpression;
use Rexpl\Lox\Expressions\GroupingExpression;
use Rexpl\Lox\Expressions\LiteralExpression;
use Rexpl\Lox\Expressions\UnaryExpression;

class Interpreter implements Visitor
{
    public function interpret(Expression $expression): void
    {
        try {
            \dump($expression->acceptVisitor($this));
        } catch (RuntimeError $e) {
            Lox::runtimeError($e);
        }
    }

    protected function evaluate(Expression $expression): mixed
    {
        return $expression->acceptVisitor($this);
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
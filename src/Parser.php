<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Expressions\BinaryExpression;
use Rexpl\Lox\Expressions\GroupingExpression;
use Rexpl\Lox\Expressions\LiteralExpression;
use Rexpl\Lox\Expressions\UnaryExpression;

class Parser
{
    protected int $current = 0;

    /**
     * @param array<\Rexpl\Lox\Token> $tokens
     */
    public function __construct(protected array $tokens) {}

    public function parse(): ?Expression
    {
        try {
            return $this->expression();
        } catch (\ParseError) {
            return null;
        }
    }

    protected function expression(): Expression
    {
        return $this->equality();
    }

    protected function equality(): Expression
    {
        $expression = $this->comparison();

        while ($this->match(TokenType::BANG_EQUAL, TokenType::EQUAL_EQUAL)) {
            $operator = $this->previous();
            $right = $this->comparison();
            $expression = new BinaryExpression($expression, $operator, $right);
        }

        return $expression;
    }

    protected function comparison(): Expression
    {
        $expression = $this->term();

        while ($this->match(TokenTYpe::GREATER, TokenType::GREATER_EQUAL, TokenType::LESS, TokenType::LESS_EQUAL)) {
            $operator = $this->previous();
            $right = $this->term();
            $expression = new BinaryExpression($expression, $operator, $right);
        }

        return $expression;
    }

    protected function term(): Expression
    {
        $expression = $this->factor();

        while ($this->match(TokenType::MINUS, TokenType::PLUS)) {
            $operator = $this->previous();
            $right = $this->factor();
            $expression = new BinaryExpression($expression, $operator, $right);
        }

        return $expression;
    }

    protected function factor(): Expression
    {
        $expression = $this->unary();

        while ($this->match(TokenType::SLASH, TokenType::STAR)) {
            $operator = $this->previous();
            $right = $this->unary();
            $expression = new BinaryExpression($expression, $operator, $right);
        }

        return $expression;
    }

    protected function unary(): Expression
    {
        if ($this->match(TokenType::BANG, TokenType::MINUS)) {

            $operator = $this->previous();
            $right = $this->unary();

            return new UnaryExpression($operator, $right);
        }

        return $this->primary();
    }

    protected function primary(): Expression
    {
        return match (true) {
            $this->match(TokenType::FALSE) => new LiteralExpression(false),
            $this->match(TokenType::TRUE) => new LiteralExpression(true),
            $this->match(TokenType::NIL) => new LiteralExpression(null),
            $this->match(TokenType::NUMBER) => new LiteralExpression((float) $this->previous()->literal),
            $this->match(TokenType::STRING) => new LiteralExpression($this->previous()->literal),
            $this->match(TokenType::LEFT_PAREN) => $this->grouping(),
            default => throw $this->error($this->peek(), 'Expected expression.'),
        };
    }

    protected function grouping(): Expression
    {
        $expression = $this->expression();
        $this->consume(TokenType::RIGHT_PAREN, 'Expected ")" after expression.');
        return new GroupingExpression($expression);
    }

    protected function match(TokenType ... $types): bool
    {
        foreach ($types as $type) {

            if ($this->check($type)) {
                $this->advance();
                return true;
            }
        }

        return false;
    }

    protected function consume(TokenType $type, string $message): Token
    {
        if ($this->check($type)) {
            return $this->advance();
        }

        throw $this->error($this->peek(), $message);
    }

    protected function error(Token $token, string $message): \ParseError
    {
        Lox::parseError($token, $message);
        return new \ParseError();
    }

    protected function check(TokenType $type): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->peek()->type === $type;
    }

    protected function advance(): Token
    {
        if (!$this->isAtEnd()) {
            $this->current++;
        }

        return $this->previous();
    }

    protected function isAtEnd(): bool
    {
        return $this->peek()->type === TokenType::EOF;
    }

    protected function peek(): Token
    {
        return $this->tokens[$this->current];
    }

    protected function previous(): Token
    {
        return $this->tokens[$this->current - 1];
    }

    protected function synchronize(): void
    {
        $this->advance();

        $exitTokens = [
            TokenType::T_CLASS,
            TokenType::FUN,
            TokenType::VAR,
            TokenType::FOR,
            TokenType::IF,
            TokenType::WHILE,
            TokenType::PRINT,
            TokenType::RETURN,
        ];

        while (!$this->isAtEnd()) {
            if (
                $this->previous()->type === TokenType::SEMICOLON
                || \in_array($this->peek()->type, $exitTokens)
            ) {
                return;
            }
        }

        $this->advance();
    }
}
<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Contracts\Expression;
use Rexpl\Lox\Contracts\Statement;
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

class Parser
{
    protected int $current = 0;

    /**
     * @param array<\Rexpl\Lox\Token> $tokens
     */
    public function __construct(protected array $tokens) {}

    public function parse(): array
    {
        $statements = [];

        while (!$this->isAtEnd()) {
            $statements[] = $this->declaration();
        }

        return $statements;
    }

    protected function declaration(): ?Statement
    {
        try {
            if ($this->match(TokenType::T_CLASS)) {
                return $this->classDeclaration();
            }

            if ($this->match(TokenType::FUN)) {
                return $this->function('function');
            }

            if ($this->match(TokenType::VAR)) {
                return $this->varDeclaration();
            }

            return $this->statement();
        } catch (\ParseError) {
            $this->synchronize();
            return null;
        }
    }

    protected function classDeclaration(): Statement
    {
        $name = $this->consume(TokenType::IDENTIFIER, 'Expected class name.');

        $superClass = null;

        if ($this->match(TokenType::LESS)) {
            $this->consume(TokenType::IDENTIFIER, 'Expected super class name.');
            $superClass = new VariableExpression($this->previous());
        }

        $this->consume(TokenType::LEFT_BRACE, 'Expected "{" before class body.');

        $methods = [];

        while (!$this->check(TokenType::RIGHT_BRACE) && !$this->isAtEnd()) {
            $methods[] = $this->function('method');
        }

        $this->consume(TokenType::RIGHT_BRACE, 'Expected "}" after class body.');

        return new ClassStatement($name, $superClass, $methods);
    }

    protected function function(string $type): Statement
    {
        $name = $this->consume(TokenType::IDENTIFIER, 'Expected function name.');
        $this->consume(TokenType::LEFT_PAREN, 'Expected "(" after function name.');

        $parameters = [];

        if (!$this->check(TokenType::RIGHT_PAREN)) {
            do {
                $parameters[] = $this->consume(TokenType::IDENTIFIER, 'Expected parameter name.');
            } while ($this->match(TokenType::COMMA) && !$this->check(TokenType::RIGHT_PAREN));
        }

        $this->consume(TokenType::RIGHT_PAREN, 'Expected ")" after parameters.');
        $this->consume(TokenType::LEFT_BRACE, 'Expected "{" before function body.');

        $body = $this->block();

        return new FunctionStatement($name, $parameters, $body);
    }

    protected function varDeclaration(): Statement
    {
        $identifier = $this->consume(TokenType::IDENTIFIER, 'Expected variable name.');
        $this->consume(TokenType::EQUAL, 'Expected "=" after variable name.');
        $expression = $this->expression();
        $this->consume(TokenType::SEMICOLON, 'Expected ";" after variable declaration.');

        return new VariableStatement($identifier, $expression);
    }

    protected function statement(): Statement
    {
        if ($this->match(TokenType::FOR)) {
            return $this->forStatement();
        }

        if ($this->match(TokenType::IF)) {
            return $this->ifStatement();
        }

        if ($this->match(TokenType::PRINT)) {
            return $this->printStatement();
        }

        if ($this->match(TokenType::RETURN)) {
            return $this->returnStatement();
        }

        if ($this->match(TokenType::WHILE)) {
            return $this->whileStatement();
        }

        if ($this->match(TokenType::LEFT_BRACE)) {
            return $this->block();
        }

        return $this->expressionStatement();
    }

    protected function forStatement(): Statement
    {
        $this->consume(TokenType::LEFT_PAREN, 'Expected "(" after "for".');

        if ($this->match(TokenType::VAR)) {
            $initializer = $this->varDeclaration();
        } elseif (!$this->match(TokenType::SEMICOLON)) {
            $initializer = $this->expressionStatement();
        }

        if (!$this->check(TokenType::SEMICOLON)) {
            $condition = $this->expression();
        } else {
            $condition = new LiteralExpression(true);
        }

        $this->consume(TokenType::SEMICOLON, 'Expected ";" after loop condition.');

        if (!$this->check(TokenType::RIGHT_PAREN)) {
            $increment = $this->expression();
        }

        $this->consume(TokenType::RIGHT_PAREN, 'Expected ")" after for clause.');

        $body = $this->statement();

        if (isset($increment)) {
            $body = new BlockStatement([
                $body,
                new ExpressionStatement($increment),
            ]);
        }

        $body = new WhileStatement($condition, $body);

        if (isset($initializer)) {
            return new BlockStatement([$initializer, $body]);
        }

        return $body;
    }

    protected function ifStatement(): IfStatement
    {
        $this->consume(TokenType::LEFT_PAREN, 'Expected "(" after "if".');
        $condition = $this->expression();
        $this->consume(TokenType::RIGHT_PAREN, 'Expected ")" after if condition.');

        $then = $this->statement();
        $else = null;

        if ($this->match(TokenType::ELSE)) {
            $else = $this->statement();
        }

        return new IfStatement($condition, $then, $else);
    }

    protected function printStatement(): PrintStatement
    {
        $expression = $this->expression();
        $this->consume(TokenType::SEMICOLON, 'Expected ";" after value.');

        return new PrintStatement($expression);
    }

    protected function returnStatement(): ReturnStatement
    {
        $keyword = $this->previous();

        if ($this->check(TokenType::SEMICOLON)) {
            $value = null;
        } else {
            $value = $this->expression();
        }

        $this->consume(TokenType::SEMICOLON, 'Expected ";" after return value.');

        return new ReturnStatement($keyword, $value);
    }

    protected function whileStatement(): WhileStatement
    {
        $this->consume(TokenType::LEFT_PAREN, 'Expected "(" after "while".');
        $condition = $this->expression();
        $this->consume(TokenType::RIGHT_PAREN, 'Expected ")" after "while" condition.');
        $body = $this->statement();

        return new WhileStatement($condition, $body);
    }

    protected function block(): BlockStatement
    {
        $statements = [];

        while (!$this->check(TokenType::RIGHT_BRACE) && !$this->isAtEnd()) {
            $statements[] = $this->declaration();
        }

        $this->consume(TokenType::RIGHT_BRACE, 'Expected "}" after block.');

        return new BlockStatement($statements);
    }

    protected function expressionStatement(): ExpressionStatement
    {
        $expression = $this->expression();
        $this->consume(TokenType::SEMICOLON, 'Expected ";" after expression.');

        return new ExpressionStatement($expression);
    }

    protected function expression(): Expression
    {
        return $this->assignment();
    }

    protected function assignment(): Expression
    {
        $expression = $this->or();

        if ($this->match(TokenType::EQUAL)) {
            $equals = $this->previous();
            $value = $this->assignment();

            if ($expression instanceof VariableExpression) {
                $name = $expression->name;
                return new AssignExpression($name, $value);
            } elseif ($expression instanceof GetExpression) {
                return new SetExpression($expression->object, $expression->name, $value);
            }

            $this->error($equals, 'Invalid assignment target.');
        }

        return $expression;
    }

    protected function or(): Expression
    {
        $expression = $this->and();

        while ($this->match(TokenType::OR)) {
            $operator = $this->previous();
            $right = $this->and();
            $expression = new LogicalExpression($expression, $operator, $right);
        }

        return $expression;
    }

    protected function and(): Expression
    {
        $expression = $this->equality();

        while ($this->match(TokenType::AND)) {
            $operator = $this->previous();
            $right = $this->equality();
            $expression = new LogicalExpression($expression, $operator, $right);
        }

        return $expression;
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

        return $this->call();
    }

    protected function call(): Expression
    {
        $expression = $this->primary();

        while (true) {
            if ($this->match(TokenType::LEFT_PAREN)) {
                $expression = $this->finishCall($expression);
            } elseif ($this->match(TokenType::DOT)) {
                $name = $this->consume(TokenType::IDENTIFIER, 'Expected property name after ".".');
                $expression = new GetExpression($expression, $name);
            } else {
                break;
            }
        }

        return $expression;
    }

    protected function finishCall(Expression $callee): CallExpression
    {
        $arguments = [];

        if (!$this->check(TokenType::RIGHT_PAREN)) {
            do {
                $arguments[] = $this->expression();
            } while ($this->match(TokenType::COMMA) && !$this->check(TokenType::RIGHT_PAREN));
        }

        $parentheses = $this->consume(TokenType::RIGHT_PAREN, 'Expected ")" after arguments.');

        return new CallExpression($callee, $parentheses, $arguments);
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
            $this->match(TokenType::SUPER) => $this->super(),
            $this->match(TokenType::THIS) => new ThisExpression($this->previous()),
            $this->match(TokenType::IDENTIFIER) => new VariableExpression($this->previous()),
            default => throw $this->error($this->peek(), 'Expected expression.'),
        };
    }

    protected function grouping(): Expression
    {
        $expression = $this->expression();
        $this->consume(TokenType::RIGHT_PAREN, 'Expected ")" after expression.');
        return new GroupingExpression($expression);
    }

    protected function super(): SuperExpression
    {
        $keyword = $this->previous();
        $this->consume(TokenType::DOT, 'Expected "." after "super".');
        $method = $this->consume(TokenType::IDENTIFIER, 'Expected superclass method name.');

        return new SuperExpression($keyword, $method);
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
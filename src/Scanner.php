<?php

declare(strict_types=1);

namespace Rexpl\Lox;

class Scanner
{
    public const KEYWORDS = [
        'and' => TokenType::AND,
        'class' => TokenType::T_CLASS,
        'else' => TokenType::ELSE,
        'false' => TokenType::FALSE,
        'for' => TokenType::FOR,
        'fun' => TokenType::FUN,
        'if' => TokenType::IF,
        'nil' => TokenType::NIL,
        'or' => TokenType::OR,
        'print' => TokenType::PRINT,
        'return' => TokenType::RETURN,
        'super' => TokenType::SUPER,
        'this' => TokenType::THIS,
        'true' => TokenType::TRUE,
        'var' => TokenType::VAR,
        'while' => TokenType::WHILE,
    ];

    /**
     * @var array<\Rexpl\Lox\Token>
     */
    protected array $tokens = [];

    protected int $start = 0;
    protected int $current = 0;
    protected int $line = 1;

    protected int $sourceLength;

    /**
     * @param string $source
     */
    public function __construct(protected string $source)
    {
        $this->sourceLength = \strlen($this->source);
    }

    /**
     * @return array<\Rexpl\Lox\Token>
     */
    public function scanTokens(): array
    {
        while (!$this->isAtEnd()) {
            $this->scanToken();
            $this->start = $this->current;
        }

        $this->addToken(TokenType::EOF);

        return $this->tokens;
    }

    protected function isAtEnd(): bool
    {
        return $this->current >= $this->sourceLength;
    }

    protected function scanToken(): void
    {
        $c = $this->advance();

        match ($c) {
            '(' => $this->addToken(TokenType::LEFT_PAREN),
            ')' => $this->addToken(TokenType::RIGHT_PAREN),
            '{' => $this->addToken(TokenType::LEFT_BRACE),
            '}' => $this->addToken(TokenType::RIGHT_BRACE),
            ',' => $this->addToken(TokenType::COMMA),
            '.' => $this->addToken(TokenType::DOT),
            '-' => $this->addToken(TokenType::MINUS),
            '+' => $this->addToken(TokenType::PLUS),
            ';' => $this->addToken(TokenType::SEMICOLON),
            '*' => $this->addToken(TokenType::STAR),
            '"' => $this->string(),
            '!' => $this->addToken($this->match('=') ? TokenType::BANG_EQUAL : TokenType::BANG),
            '=' => $this->addToken($this->match('=') ? TokenType::EQUAL_EQUAL : TokenType::EQUAL),
            '<' => $this->addToken($this->match('=') ? TokenType::LESS_EQUAL : TokenType::LESS),
            '>' => $this->addToken($this->match('=') ? TokenType::GREATER_EQUAL : TokenType::GREATER),
            '/' => $this->slashOrComment(),
            ' ', "\r", "\t" => null,
            "\n" => $this->line++,
            default => $this->handleDefault($c),
        };
    }

    protected function handleDefault(string $c): void
    {
        if (\is_numeric($c)) {
            $this->number($c);
            return;
        }

        if (\ctype_alpha($c)) {
            $this->identifier($c);
            return;
        }

        Lox::error($this->line, 'Unexpected character.');
    }

    protected function advance(): string
    {
        return $this->source[$this->current++];
    }

    protected function match(string $expected): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        $result = $this->source[$this->current] === $expected;

        if ($result) {
            $this->current++;
        }

        return $result;
    }

    protected function peek(): string
    {
        if ($this->isAtEnd()) {
            return "\0";
        }

        return $this->source[$this->current];
    }

    protected function peekNext(): string
    {
        if ($this->current + 1 >= $this->sourceLength) {
            return "\0";
        }

        return $this->source[$this->current + 1];
    }

    protected function slashOrComment(): void
    {
        if ($this->peek() === '/') {
            while ($this->peek() !== "\n" && !$this->isAtEnd()) {
                $this->advance();
            }
        } else {
            $this->addToken(TokenType::SLASH);
        }
    }

    protected function string(): void
    {
        $next = $this->peek();
        $value = '';

        while ($next !== '"' && !$this->isAtEnd()) {

            if ($next === "\n") {
                $this->line++;
            }

            $value .= $this->advance();
            $next = $this->peek();
        }

        if ($this->isAtEnd()) {
            Lox::error($this->line, 'Unterminated string.');
            return;
        }

        $this->advance();
        $this->addToken(TokenType::STRING, $value);
    }

    protected function number(string $number): void
    {
        while (is_numeric($this->peek()) && !$this->isAtEnd()) {
            $number .= $this->advance();
        }

        // Look for a fractional part.
        if ($this->peek() === '.' && \is_numeric($this->peekNext())) {

            $number .= '.';
            $this->advance();

            while (is_numeric($this->peek()) && !$this->isAtEnd()) {
                $number .= $this->advance();
            }
        }

        $this->addToken(TokenType::NUMBER, $number);
    }

    protected function identifier(string $first): void
    {
        $characters = $first;

        while (\ctype_alpha($this->peek()) || $this->peek() === '_') {
            $characters .= $this->advance();
        }

        if (isset(static::KEYWORDS[$characters])) {
            $this->addToken(static::KEYWORDS[$characters], $characters);
        } else {
            $this->addToken(TokenType::IDENTIFIER, $characters);
        }
    }

    protected function addToken(TokenType $type, mixed $literal = null): void
    {
        $lexeme = \substr($this->source, $this->start, $this->current);
        $this->tokens[] = new Token($type, $lexeme, $literal, $this->line);
    }
}
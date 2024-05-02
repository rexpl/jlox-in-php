<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Exceptions\RuntimeError;
use Rexpl\Lox\Statements\ExpressionStatement;
use Rexpl\Lox\Statements\PrintStatement;

class Lox
{
    private static bool $hadError = false;
    private static bool $hasRuntimeError = false;
    private static Interpreter $interpreter;

    public static function main(array $args): void
    {
        self::$interpreter = new Interpreter();

        $argumentsCount = count($args);

        if ($argumentsCount > 2) {
            echo "Usage: php lox [script]\n";
            exit(64);
        } else if ($argumentsCount == 2) {
            self::runFile($args[1]);
        } else {
            self::runPrompt();
        }
    }

    private static function runFile(string $path): void
    {
        $source = \file_get_contents($path);
        self::run($source, false);

        if (self::$hadError) {
            exit(65);
        }

        if (self::$hasRuntimeError) {
            exit(70);
        }
    }

    private static function runPrompt(): void
    {
        while (true) {
            $line = \readline('> ');

            if (!$line) {
                break;
            }

            if (!\str_ends_with($line, ';')) {
                $line .= ';';
            }

            self::run($line, true);
            self::$hadError = false;
        }
    }

    private static function run(string $source, bool $repl): void
    {
        $scanner = new Scanner($source);
        $tokens = $scanner->scanTokens();

        $parser = new Parser($tokens);
        $statements = $parser->parse();

        if (self::$hadError) {
            return;
        }

        // If the input is only a single expression we evaluate it and print it for convenience.
        if ($repl && \count($statements) === 1 && $statements[0] instanceof ExpressionStatement) {
            $statements[0] = new PrintStatement($statements[0]->expression);
        }

        self::$interpreter->interpret($statements);
    }

    public static function error(int $line, string $message): void
    {
        self::$hadError = true;
        self::report($line, $message);
    }

    public static function parseError(Token $token, string $message): void
    {
        self::$hadError = true;

        if ($token->type === TokenType::EOF) {
            self::report($token->line, $message, ' at end', 'ParseError');
        } else {
            self::report($token->line, $message, \sprintf(' at "%s"', $token->lexeme), 'ParseError');
        }
    }

    public static function runtimeError(RuntimeError $error): void
    {
        self::report(
            $error->token->line,
            $error->userMessage,
            sprintf(' at "%s"', $error->token->lexeme),
            'RuntimeError'
        );
        self::$hasRuntimeError = true;
    }

    private static function report(int $line, string $message, string $where = '', string $errorType = 'Error'): void
    {
        echo \sprintf("\033[31m[line %d] %s%s: %s\033[0m\n", $line, $errorType, $where, $message);
    }
}
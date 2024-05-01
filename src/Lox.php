<?php

declare(strict_types=1);

namespace Rexpl\Lox;

class Lox
{
    private static bool $hadError = false;

    public static function main(array $args): void
    {
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
    }

    private static function runPrompt(): void
    {
        while (true) {
            $line = \readline('> ');

            if (!$line) {
                break;
            }

            self::run($line, true);
            self::$hadError = false;
        }
    }

    private static function run(string $source, bool $repl): void
    {
        $scanner = new Scanner($source);
        $tokens = $scanner->scanTokens();

        foreach ($tokens as $token) {
            echo $token . \PHP_EOL;
        }
    }

    public static function error(int $line, string $message): void
    {
        self::report($line, $message);
    }

    private static function report(int $line, string $message, string $where = ''): void
    {
        echo \sprintf("\033[31m[line %d] Error%s: %s\033[0m\n", $line, $where, $message);
        self::$hadError = true;
    }
}
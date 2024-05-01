<?php

declare(strict_types=1);

namespace Rexpl\Lox\Exceptions;

use Rexpl\Lox\Token;

class RuntimeError extends \RuntimeException
{
    /**
     * @param \Rexpl\Lox\Token $token
     * @param string $userMessage
     */
    public function __construct(public Token $token, public string $userMessage) {}
}
<?php

declare(strict_types=1);

namespace Rexpl\Lox;

use Rexpl\Lox\Contracts\LoxCallable;
use Rexpl\Lox\Exceptions\FlowExceptions\LoxReturn;
use Rexpl\Lox\Statements\FunctionStatement;

class LoxFunction implements LoxCallable
{
    /**
     * @var bool
     */
    public bool $isInitializer = false;

    /**
     * @param \Rexpl\Lox\Statements\FunctionStatement $declaration
     * @param \Rexpl\Lox\Environment $closure
     */
    public function __construct(protected FunctionStatement $declaration, protected Environment $closure) {}

    public function arity(): int
    {
        return \count($this->declaration->params);
    }

    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        $environment = new Environment($this->closure);

        foreach ($this->declaration->params as $key => $param) {
            $environment->define($param->literal, $arguments[$key]);
        }

        try {
            $interpreter->executeBlock($this->declaration->body->statements, new Environment($environment));
        } catch (LoxReturn $flowReturn) {
            return $this->isInitializer
                ? $this->closure->getAt(0, 'this')
                : $flowReturn->value;
        }

        return null;
    }

    public function bind(LoxInstance $instance): LoxFunction
    {
        $environment = new Environment($this->closure);
        $environment->define('this', $instance);

        return new LoxFunction($this->declaration, $environment);
    }
}
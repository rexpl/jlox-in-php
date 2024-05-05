<?php

declare(strict_types=1);

namespace Rexpl\Lox;

enum ClassType
{
    case None;
    case T_Class;
    case SubClass;
}

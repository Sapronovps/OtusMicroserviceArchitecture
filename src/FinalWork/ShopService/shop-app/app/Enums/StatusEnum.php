<?php

namespace App\Enums;

enum StatusEnum: int
{
    case CREATED = 1;
    CASE SHIPPED = 2;

    public function getName(): string
    {
        return match($this) {
            self::CREATED => 'Прибыл',
            self::SHIPPED => 'Выдан',
        };
    }
}

<?php

namespace App\Enums;

enum StatusEnum: int
{
    case FREE = 1;
    CASE BUSY = 2;

    public function getName(): string
    {
        return match($this) {
            self::FREE => 'Свободен',
            self::BUSY => 'Занят',
        };
    }
}

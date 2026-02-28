<?php

namespace App\Enums;

enum TodoStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Overdue => 'Overdue',
        };
    }
}

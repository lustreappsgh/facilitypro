<?php

namespace App\Models\Concerns;

use DateTimeInterface;

trait FormatsDates
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('M j, Y');
    }
}

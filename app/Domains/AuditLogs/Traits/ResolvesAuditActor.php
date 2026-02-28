<?php

namespace App\Domains\AuditLogs\Traits;

use RuntimeException;

trait ResolvesAuditActor
{
    protected function resolveActorId(): int
    {
        $actorId = auth()->id();

        if (! is_int($actorId)) {
            throw new RuntimeException('Audit log actor is required.');
        }

        return $actorId;
    }
}

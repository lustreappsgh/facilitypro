<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $search = $request->string('search')->trim()->toString();
        $action = $request->string('action')->trim()->toString();
        $actor = $request->string('actor')->trim()->toString();
        $auditableType = $request->string('auditable_type')->trim()->toString();
        $auditableId = $request->string('auditable_id')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        $query = AuditLog::query()
            ->with('actor');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('action', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%")
                    ->orWhereHas('actor', function ($actorQuery) use ($search) {
                        $actorQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($actor !== '') {
            $query->whereHas('actor', function ($actorQuery) use ($actor) {
                $actorQuery->where('name', 'like', "%{$actor}%");
            });
        }

        if ($auditableType !== '') {
            $query->where('auditable_type', $auditableType);
        }

        if ($auditableId !== '' && is_numeric($auditableId)) {
            $query->where('auditable_id', (int) $auditableId);
        }

        if ($startDate !== '') {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $logs = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $logs->getCollection()->transform(function (AuditLog $log) {
            $log->setAttribute('changes', $this->diffChanges($log->before, $log->after));
            return $log;
        });

        return Inertia::render('AuditLogs/AdminViewer', [
            'data' => [
                'logs' => $logs,
            ],
            'filters' => [
                'search' => $search ?: null,
                'action' => $action ?: null,
                'actor' => $actor ?: null,
                'auditable_type' => $auditableType ?: null,
                'auditable_id' => $auditableId ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'dashboard' => route('dashboard'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function governance(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $allowedActions = [
            'payment.created',
            'payment.approved',
            'payment.rejected',
            'maintenance_request.created',
            'maintenance_request.started',
            'maintenance_request.completed',
            'maintenance_request.updated',
            'work_order.created',
            'work_order.updated',
        ];

        $action = $request->string('action')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        $query = AuditLog::query()
            ->with('actor')
            ->whereIn('action', $allowedActions);

        if ($action !== '' && in_array($action, $allowedActions, true)) {
            $query->where('action', $action);
        }

        if ($startDate !== '') {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return Inertia::render('AuditLogs/Governance', [
            'data' => [
                'logs' => $query->latest()->paginate(20)->withQueryString(),
            ],
            'filters' => [
                'action' => $action ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
            'actions' => $allowedActions,
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'dashboard' => route('dashboard'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    private function diffChanges(?array $before, ?array $after): array
    {
        $before = $before ?? [];
        $after = $after ?? [];

        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $changes = [];

        foreach ($keys as $key) {
            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;

            if ($beforeValue === $afterValue) {
                continue;
            }

            $changes[] = [
                'field' => $key,
                'before' => $beforeValue,
                'after' => $afterValue,
            ];
        }

        return $changes;
    }
}

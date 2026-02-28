<?php

namespace App\Http\Controllers;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\Maintenance\Requests\RequestTypeRequest;
use App\Models\RequestType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RequestTypesController extends Controller
{
    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function index(Request $request)
    {
        if (! $request->user()->can('request_types.manage')) {
            abort(403);
        }

        $search = $request->string('search')->trim()->toString();

        $types = RequestType::query()
            ->withCount('maintenanceRequests')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('RequestTypes/Index', [
            'data' => [
                'requestTypes' => $types,
            ],
            'filters' => [
                'search' => $search ?: null,
            ],
        ]);
    }

    public function store(RequestTypeRequest $request)
    {
        if (! $request->user()->can('request_types.manage')) {
            abort(403);
        }

        $payload = $request->validated();
        $requestType = RequestType::create($payload);

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $request->user()->id,
            action: 'request_type.created',
            auditable_type: $requestType->getMorphClass(),
            auditable_id: $requestType->id,
            before: null,
            after: $requestType->getAttributes(),
        ));

        return back()->with('success', 'Request type created.');
    }

    public function update(RequestTypeRequest $request, RequestType $requestType)
    {
        if (! $request->user()->can('request_types.manage')) {
            abort(403);
        }

        $before = $requestType->getOriginal();
        $requestType->update($request->validated());

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $request->user()->id,
            action: 'request_type.updated',
            auditable_type: $requestType->getMorphClass(),
            auditable_id: $requestType->id,
            before: $before,
            after: $requestType->getAttributes(),
        ));

        return back()->with('success', 'Request type updated.');
    }

    public function destroy(Request $request, RequestType $requestType)
    {
        if (! $request->user()->can('request_types.manage')) {
            abort(403);
        }

        if ($requestType->maintenanceRequests()->exists()) {
            return back()->withErrors([
                'requestType' => 'Request type is in use and cannot be deleted.',
            ]);
        }

        $before = $requestType->getAttributes();
        $requestType->delete();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $request->user()->id,
            action: 'request_type.deleted',
            auditable_type: $requestType->getMorphClass(),
            auditable_id: $requestType->id,
            before: $before,
            after: null,
        ));

        return back()->with('success', 'Request type deleted.');
    }
}

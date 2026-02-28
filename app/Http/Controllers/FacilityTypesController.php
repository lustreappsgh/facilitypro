<?php

namespace App\Http\Controllers;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\Facilities\Requests\FacilityTypeRequest;
use App\Models\FacilityType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacilityTypesController extends Controller
{
    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function index(Request $request)
    {
        if (! $request->user()->can('facility_types.manage')) {
            abort(403);
        }

        $search = $request->string('search')->trim()->toString();

        $types = FacilityType::query()
            ->withCount('facilities')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('FacilityTypes/Index', [
            'data' => [
                'facilityTypes' => $types,
            ],
            'filters' => [
                'search' => $search ?: null,
            ],
        ]);
    }

    public function store(FacilityTypeRequest $request)
    {
        if (! $request->user()->can('facility_types.manage')) {
            abort(403);
        }

        $payload = $request->validated();
        $facilityType = FacilityType::create($payload);

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $request->user()->id,
            action: 'facility_type.created',
            auditable_type: $facilityType->getMorphClass(),
            auditable_id: $facilityType->id,
            before: null,
            after: $facilityType->getAttributes(),
        ));

        return back()->with('success', 'Facility type created.');
    }

    public function update(FacilityTypeRequest $request, FacilityType $facilityType)
    {
        if (! $request->user()->can('facility_types.manage')) {
            abort(403);
        }

        $before = $facilityType->getOriginal();
        $facilityType->update($request->validated());

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $request->user()->id,
            action: 'facility_type.updated',
            auditable_type: $facilityType->getMorphClass(),
            auditable_id: $facilityType->id,
            before: $before,
            after: $facilityType->getAttributes(),
        ));

        return back()->with('success', 'Facility type updated.');
    }

    public function destroy(Request $request, FacilityType $facilityType)
    {
        if (! $request->user()->can('facility_types.manage')) {
            abort(403);
        }

        if ($facilityType->facilities()->exists()) {
            return back()->withErrors([
                'facilityType' => 'Facility type is in use and cannot be deleted.',
            ]);
        }

        $before = $facilityType->getAttributes();
        $facilityType->delete();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $request->user()->id,
            action: 'facility_type.deleted',
            auditable_type: $facilityType->getMorphClass(),
            auditable_id: $facilityType->id,
            before: $before,
            after: null,
        ));

        return back()->with('success', 'Facility type deleted.');
    }
}

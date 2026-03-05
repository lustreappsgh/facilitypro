<?php

namespace App\Http\Controllers;

use App\Domains\Maintenance\Actions\CreateWorkOrderAction;
use App\Domains\Maintenance\Actions\UpdateWorkOrderAction;
use App\Domains\Maintenance\DTOs\WorkOrderData;
use App\Domains\Maintenance\Requests\WorkOrderRequest;
use App\Domains\Maintenance\Services\MaintenanceService;
use App\Domains\Payments\Requests\PaymentUpdateRequest;
use App\Enums\MaintenanceStatus;
use App\Models\Facility;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkOrder;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected CreateWorkOrderAction $createWorkOrderAction,
        protected UpdateWorkOrderAction $updateWorkOrderAction,
        protected MaintenanceService $maintenanceService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', WorkOrder::class);

        $query = WorkOrder::userVisible($request->user())->with(['vendor', 'maintenanceRequest.facility']);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $vendor = $request->string('vendor')->trim()->toString();
        $facility = $request->string('facility')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('maintenanceRequest', function ($requestQuery) use ($search) {
                    $requestQuery->where('description', 'like', "%{$search}%")
                        ->orWhereHas('facility', function ($facilityQuery) use ($search) {
                            $facilityQuery->where('name', 'like', "%{$search}%");
                        });
                })->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                    $vendorQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($vendor !== '') {
            $query->where('vendor_id', $vendor);
        }

        if ($facility !== '') {
            $query->whereHas('maintenanceRequest', function ($requestQuery) use ($facility) {
                $requestQuery->where('facility_id', $facility);
            });
        }

        if ($startDate !== '') {
            $query->whereDate('scheduled_date', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('scheduled_date', '<=', $endDate);
        }

        return Inertia::render('WorkOrders/Index', [
            'workOrders' => $query->latest()->paginate(10)->withQueryString(),
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
            'facilities' => Facility::maintenanceFacilities($request->user())
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'vendor' => $vendor ?: null,
                'facility' => $facility ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
        ]);
    }

    public function oversight(Request $request)
    {
        $this->authorize('viewAny', WorkOrder::class);

        $query = WorkOrder::userVisible($request->user())->with(['vendor', 'maintenanceRequest.facility']);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $vendor = $request->string('vendor')->trim()->toString();
        $facility = $request->string('facility')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('maintenanceRequest', function ($requestQuery) use ($search) {
                    $requestQuery->where('description', 'like', "%{$search}%")
                        ->orWhereHas('facility', function ($facilityQuery) use ($search) {
                            $facilityQuery->where('name', 'like', "%{$search}%");
                        });
                })->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                    $vendorQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($vendor !== '') {
            $query->where('vendor_id', $vendor);
        }

        if ($facility !== '') {
            $query->whereHas('maintenanceRequest', function ($requestQuery) use ($facility) {
                $requestQuery->where('facility_id', $facility);
            });
        }

        if ($startDate !== '') {
            $query->whereDate('scheduled_date', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('scheduled_date', '<=', $endDate);
        }

        return Inertia::render('WorkOrders/Oversight', [
            'workOrders' => $query->latest()->paginate(10)->withQueryString(),
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
            'facilities' => Facility::maintenanceFacilities($request->user())
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'vendor' => $vendor ?: null,
                'facility' => $facility ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', WorkOrder::class);

        return Inertia::render('WorkOrders/Create', [
            'maintenanceRequests' => MaintenanceRequest::maintenanceScope($request->user())
                ->with(['facility', 'requestType'])
                ->whereIn('status', [
                    MaintenanceStatus::Submitted->value,
                    MaintenanceStatus::Pending->value,
                    MaintenanceStatus::Approved->value,
                ])
                ->get(),
            'selectedRequestId' => $request->integer('maintenance_request_id') ?: null,
            'vendors' => Vendor::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function bulkCreate(Request $request)
    {
        $this->authorize('create', WorkOrder::class);

        $requestedIds = collect($request->input('request_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $intent = $request->string('intent')->toString();
        if (! in_array($intent, ['review', 'create'], true)) {
            $intent = null;
        }

        $maintenanceRequests = MaintenanceRequest::maintenanceScope($request->user())
            ->with(['facility.facilityType', 'requestType'])
            ->whereIn('status', MaintenanceStatus::approvalQueue())
            ->whereDoesntHave('workOrders')
            ->when(
                $requestedIds->isNotEmpty(),
                fn ($builder) => $builder->whereIn('id', $requestedIds->all()),
            )
            ->orderByDesc('created_at')
            ->get();

        $facilityManagerIds = $maintenanceRequests
            ->pluck('facility.managed_by')
            ->filter()
            ->unique()
            ->values();

        return Inertia::render('WorkOrders/BulkCreate', [
            'facilityManagers' => User::query()
                ->active()
                ->whereIn('id', $facilityManagerIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'vendors' => Vendor::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'maintenanceRequests' => $maintenanceRequests->map(fn (MaintenanceRequest $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'cost' => $item->cost,
                'facility' => $item->facility ? [
                    'id' => $item->facility->id,
                    'name' => $item->facility->name,
                    'managed_by' => $item->facility->managed_by,
                ] : null,
                'requestType' => $item->requestType ? [
                    'id' => $item->requestType->id,
                    'name' => $item->requestType->name,
                ] : null,
            ])->values(),
            'selection' => [
                'request_ids' => $requestedIds->all(),
                'intent' => $intent,
            ],
        ]);
    }

    public function store(WorkOrderRequest $request)
    {
        $this->authorize('create', WorkOrder::class);

        $data = WorkOrderData::fromRequest($request->validated());
        try {
            $this->createWorkOrderAction->execute($data);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'maintenance_request_id' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('work-orders.index')->with('success', 'Work Order assigned.');
    }

    public function bulkStore(Request $request)
    {
        $this->authorize('create', WorkOrder::class);

        $validated = $request->validate([
            'bulk_orders' => ['required', 'array', 'min:1'],
            'bulk_orders.*.maintenance_request_id' => ['required', 'exists:maintenance_requests,id'],
            'bulk_orders.*.estimated_cost' => ['nullable', 'numeric'],
            'bulk_orders.*.vendor_id' => ['nullable', 'exists:vendors,id'],
            'bulk_orders.*.review_action' => ['required', 'in:approve,reject'],
        ]);

        $bulkOrders = collect($validated['bulk_orders'])
            ->unique('maintenance_request_id')
            ->values();

        $requestIds = $bulkOrders->pluck('maintenance_request_id')->all();
        $requests = MaintenanceRequest::maintenanceScope($request->user())
            ->whereIn('id', $requestIds)
            ->withCount('workOrders')
            ->get()
            ->keyBy('id');

        $approvedCount = 0;
        $rejectedCount = 0;
        $errors = [];

        foreach ($bulkOrders as $item) {
            $maintenanceRequestId = (int) $item['maintenance_request_id'];
            $maintenanceRequest = $requests->get($maintenanceRequestId);
            $reviewAction = $item['review_action'] ?? 'approve';

            if (! $maintenanceRequest) {
                $errors[] = "Request #{$maintenanceRequestId} is outside your scope.";
                continue;
            }

            if (($maintenanceRequest->work_orders_count ?? 0) > 0) {
                $errors[] = "Request #{$maintenanceRequestId} already has a work order.";
                continue;
            }

            try {
                $this->authorize('review', $maintenanceRequest);

                DB::transaction(function () use ($reviewAction, $maintenanceRequest, $maintenanceRequestId, $item) {
                    if ($reviewAction === 'reject') {
                        $this->maintenanceService->reject($maintenanceRequest);
                        return;
                    }

                    if (empty($item['vendor_id'])) {
                        throw new DomainException('Vendor is required for approval.');
                    }

                    if (! isset($item['estimated_cost']) || $item['estimated_cost'] === '') {
                        throw new DomainException('Estimated cost is required for approval.');
                    }

                    $this->maintenanceService->review($maintenanceRequest);

                    $this->createWorkOrderAction->execute(WorkOrderData::fromRequest([
                        'maintenance_request_id' => $maintenanceRequestId,
                        'vendor_id' => $item['vendor_id'],
                        'estimated_cost' => $item['estimated_cost'] ?? $maintenanceRequest->cost,
                        'status' => 'assigned',
                    ]));
                });

                if ($reviewAction === 'reject') {
                    $rejectedCount++;
                } else {
                    $approvedCount++;
                }
            } catch (DomainException $exception) {
                $errors[] = "Request #{$maintenanceRequestId}: ".$exception->getMessage();
            } catch (\Throwable $exception) {
                $errors[] = "Request #{$maintenanceRequestId}: ".$exception->getMessage();
            }
        }

        if ($approvedCount + $rejectedCount === 0) {
            return back()->withErrors([
                'bulk_orders' => 'No requests were processed. '.collect($errors)->take(3)->implode(' '),
            ]);
        }

        $messageParts = [];
        if ($approvedCount > 0) {
            $messageParts[] = "{$approvedCount} request(s) approved and converted to work orders.";
        }
        if ($rejectedCount > 0) {
            $messageParts[] = "{$rejectedCount} request(s) rejected.";
        }
        $message = implode(' ', $messageParts);
        if ($errors !== []) {
            $message .= ' '.count($errors).' item(s) skipped.';
        }

        return redirect()
            ->route('work-orders.index')
            ->with('success', $message);
    }

    public function show(WorkOrder $workOrder)
    {
        $this->authorize('view', $workOrder);

        return Inertia::render('WorkOrders/Show', [
            'workOrder' => $workOrder->load(['vendor', 'maintenanceRequest.facility', 'payment']),
        ]);
    }

    public function edit(WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        return Inertia::render('WorkOrders/Edit', [
            'workOrder' => $workOrder->load(['vendor', 'maintenanceRequest', 'payment']),
            'maintenanceRequests' => MaintenanceRequest::maintenanceScope(request()->user())
                ->with(['facility', 'requestType'])
                ->whereIn('status', [
                    MaintenanceStatus::Submitted->value,
                    MaintenanceStatus::Pending->value,
                    MaintenanceStatus::Approved->value,
                ])
                ->get(),
            'vendors' => Vendor::where('status', 'active')->get(),
        ]);
    }

    public function update(WorkOrderRequest $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        try {
            $this->updateWorkOrderAction->execute($workOrder, $request->validated());
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Work order updated.');
    }

    public function updatePayment(PaymentUpdateRequest $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        $payment = $workOrder->payment;
        if ($payment && in_array($payment->status, ['approved', 'paid'], true)) {
            return back()->withErrors([
                'cost' => 'Approved or paid payments cannot be updated.',
            ]);
        }

        $data = $request->validated();

        if (! $payment) {
            $payment = Payment::create([
                'maintenance_request_id' => $workOrder->maintenance_request_id,
                'work_order_id' => $workOrder->id,
                'cost' => $data['cost'],
                'amount_payed' => 0,
                'comments' => $data['comments'] ?? null,
                'status' => 'pending',
            ]);
        } else {
            $payment->fill([
                'cost' => $data['cost'],
                'comments' => $data['comments'] ?? null,
                'status' => 'pending',
            ])->save();
        }

        return back()->with('success', 'Payment submitted for approval.');
    }

    public function adminIndex(Request $request)
    {
        $this->authorize('viewAny', WorkOrder::class);

        $query = WorkOrder::userVisible($request->user())->with(['vendor', 'maintenanceRequest.facility']);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $vendor = $request->string('vendor')->trim()->toString();
        $facility = $request->string('facility')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('maintenanceRequest', function ($requestQuery) use ($search) {
                    $requestQuery->where('description', 'like', "%{$search}%")
                        ->orWhereHas('facility', function ($facilityQuery) use ($search) {
                            $facilityQuery->where('name', 'like', "%{$search}%");
                        });
                })->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                    $vendorQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($vendor !== '') {
            $query->where('vendor_id', $vendor);
        }

        if ($facility !== '') {
            $query->whereHas('maintenanceRequest', function ($requestQuery) use ($facility) {
                $requestQuery->where('facility_id', $facility);
            });
        }

        if ($startDate !== '') {
            $query->whereDate('scheduled_date', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('scheduled_date', '<=', $endDate);
        }

        return Inertia::render('WorkOrders/AdminIndex', [
            'workOrders' => $query->latest()->paginate(10)->withQueryString(),
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
            'facilities' => Facility::maintenanceFacilities($request->user())
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'vendor' => $vendor ?: null,
                'facility' => $facility ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
        ]);
    }
}

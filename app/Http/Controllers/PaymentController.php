<?php

namespace App\Http\Controllers;

use App\Domains\Payments\Requests\ApprovePaymentRequest;
use App\Domains\Payments\Requests\RejectPaymentRequest;
use App\Domains\Payments\Services\PaymentService;
use App\Models\Facility;
use App\Models\Payment;
use App\Models\Vendor;
use App\Models\WorkOrder;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $user = $request->user();

        $query = Payment::query()
            ->with([
                'maintenanceRequest.facility',
                'maintenanceRequest.requestType',
                'workOrder.vendor',
            ]);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $facility = $request->string('facility')->trim()->toString();
        $vendor = $request->string('vendor')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        if (! $user->can('maintenance.manage_all')) {
            $query->whereHas('maintenanceRequest', fn ($requestQuery) => $requestQuery->maintenanceScope($user));
        }

        if ($search !== '') {
            $query->whereHas('maintenanceRequest', function ($requestQuery) use ($search) {
                $requestQuery->where('description', 'like', "%{$search}%")
                    ->orWhereHas('facility', function ($facilityQuery) use ($search) {
                        $facilityQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($facility !== '') {
            $query->whereHas('maintenanceRequest', function ($requestQuery) use ($facility) {
                $requestQuery->where('facility_id', $facility);
            });
        }

        if ($vendor !== '') {
            $query->whereHas('workOrder', function ($workOrderQuery) use ($vendor) {
                $workOrderQuery->where('vendor_id', $vendor);
            });
        }

        if ($startDate !== '') {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $payments = $query->latest()->paginate(10)->withQueryString();

        $facilityQuery = Facility::maintenanceFacilities($user)->orderBy('name');
        $vendorQuery = Vendor::query()->orderBy('name');

        if (! $user->can('maintenance.manage_all')) {
            $vendorIds = WorkOrder::query()
                ->whereHas('maintenanceRequest', fn ($requestQuery) => $requestQuery->maintenanceScope($user))
                ->pluck('vendor_id')
                ->unique()
                ->filter()
                ->values();

            if ($vendorIds->isNotEmpty()) {
                $vendorQuery->whereIn('id', $vendorIds);
            } else {
                $vendorQuery->whereRaw('1 = 0');
            }
        }

        return Inertia::render('Payments/Index', [
            'data' => [
                'payments' => $payments,
            ],
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'facility' => $facility ?: null,
                'vendor' => $vendor ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
            'facilities' => $facilityQuery->get(['id', 'name']),
            'vendors' => $vendorQuery->get(['id', 'name']),
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'approvals' => route('payment-approvals.index'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function approvals(Request $request): Response
    {
        $this->authorize('approveAny', Payment::class);

        $query = Payment::query()
            ->with([
                'maintenanceRequest.facility',
                'maintenanceRequest.requestType',
                'workOrder.vendor',
            ]);

        $status = $request->string('status')->trim()->toString();
        $facility = $request->string('facility')->trim()->toString();
        $search = $request->string('search')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();
        $minAmount = $request->string('min_amount')->trim()->toString();
        $maxAmount = $request->string('max_amount')->trim()->toString();

        if ($status === '') {
            $status = 'pending';
        }

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->whereHas('maintenanceRequest', function ($requestQuery) use ($search) {
                $requestQuery->where('description', 'like', "%{$search}%")
                    ->orWhereHas('facility', function ($facilityQuery) use ($search) {
                        $facilityQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($facility !== '') {
            $query->whereHas('maintenanceRequest', function ($requestQuery) use ($facility) {
                $requestQuery->where('facility_id', $facility);
            });
        }

        if ($startDate !== '') {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if ($minAmount !== '' && is_numeric($minAmount)) {
            $query->where('cost', '>=', (int) $minAmount);
        }

        if ($maxAmount !== '' && is_numeric($maxAmount)) {
            $query->where('cost', '<=', (int) $maxAmount);
        }

        $payments = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Payments/Approvals', [
            'data' => [
                'payments' => $payments,
            ],
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'facility' => $facility ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
                'min_amount' => $minAmount ?: null,
                'max_amount' => $maxAmount ?: null,
            ],
            'facilities' => Facility::orderBy('name')->get(['id', 'name']),
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'index' => route('payments.index'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function show(Payment $payment): Response
    {
        $this->authorize('view', $payment);

        $payment->load([
            'maintenanceRequest.facility',
            'maintenanceRequest.requestType',
            'approvals.approver',
            'workOrder.vendor',
        ]);

        return Inertia::render('Payments/Show', [
            'payment' => $payment,
            'workOrder' => $payment->workOrder,
        ]);
    }

    public function approve(ApprovePaymentRequest $request, Payment $payment)
    {
        $this->authorize('approve', $payment);

        try {
            $this->paymentService->approve(
                $payment,
                $request->user()->id,
                $request->validated('comments')
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'comments' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Payment approved.');
    }

    public function reject(RejectPaymentRequest $request, Payment $payment)
    {
        $this->authorize('reject', $payment);

        try {
            $this->paymentService->reject(
                $payment,
                $request->user()->id,
                $request->validated('comments')
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'comments' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Payment rejected.');
    }

    public function adminIndex(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::query()
            ->with([
                'maintenanceRequest.facility',
                'maintenanceRequest.requestType',
                'workOrder.vendor',
            ]);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $facility = $request->string('facility')->trim()->toString();
        $vendor = $request->string('vendor')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        if ($search !== '') {
            $query->whereHas('maintenanceRequest', function ($requestQuery) use ($search) {
                $requestQuery->where('description', 'like', "%{$search}%")
                    ->orWhereHas('facility', function ($facilityQuery) use ($search) {
                        $facilityQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($facility !== '') {
            $query->whereHas('maintenanceRequest', function ($requestQuery) use ($facility) {
                $requestQuery->where('facility_id', $facility);
            });
        }

        if ($vendor !== '') {
            $query->whereHas('workOrder', function ($workOrderQuery) use ($vendor) {
                $workOrderQuery->where('vendor_id', $vendor);
            });
        }

        if ($startDate !== '') {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $payments = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Payments/AdminIndex', [
            'data' => [
                'payments' => $payments,
            ],
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'facility' => $facility ?: null,
                'vendor' => $vendor ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
            'facilities' => Facility::orderBy('name')->get(['id', 'name']),
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'index' => route('payments.index'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }
}

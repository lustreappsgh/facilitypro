<?php

namespace App\Http\Controllers;

use App\Domains\Vendors\DTOs\VendorData;
use App\Domains\Vendors\Requests\VendorRequest;
use App\Domains\Vendors\Services\VendorService;
use App\Models\Vendor;
use App\Models\WorkOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected VendorService $vendorService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Vendor::class);

        $query = Vendor::query();

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $serviceType = $request->string('service_type')->trim()->toString();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($serviceType !== '') {
            $query->where('service_type', $serviceType);
        }

        return Inertia::render('Vendors/Index', [
            'vendors' => $query->latest()->paginate(10)->withQueryString(),
            'serviceTypes' => Vendor::query()
                ->whereNotNull('service_type')
                ->distinct()
                ->orderBy('service_type')
                ->pluck('service_type'),
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'service_type' => $serviceType ?: null,
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Vendor::class);

        return Inertia::render('Vendors/Create');
    }

    public function store(VendorRequest $request)
    {
        $this->authorize('create', Vendor::class);

        $data = VendorData::fromRequest($request->validated());
        $vendor = $this->vendorService->create($data);

        $redirectTo = $request->input('redirect_to');
        if ($redirectTo) {
            return redirect()->to($redirectTo)
                ->with('success', 'Vendor created.')
                ->with('created_vendor', [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                ]);
        }

        return redirect()->route('vendors.index')->with('success', 'Vendor created.');
    }

    public function show(Vendor $vendor)
    {
        $this->authorize('view', $vendor);

        return Inertia::render('Vendors/Show', [
            'vendor' => $vendor,
            'recentWorkOrders' => WorkOrder::with(['maintenanceRequest'])
                ->where('vendor_id', $vendor->id)
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Domains\Maintenance\Services\MaintenanceManagerDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceManagerDashboardController extends Controller
{
    public function __construct(
        protected MaintenanceManagerDashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless(
            $user->can('work_orders.create') || $user->can('maintenance.start'),
            403
        );

        $summary = $this->dashboardService->summary($user);

        return Inertia::render('Maintenance/Dashboard', [
            'metrics' => $summary['metrics'],
            'queues' => $summary['queues'],
        ]);
    }
}

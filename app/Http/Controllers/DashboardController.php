<?php

namespace App\Http\Controllers;

use App\Domains\Reports\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $data = $this->dashboardService->forUser($request->user());

        return Inertia::render('Dashboard', [
            'data' => $data,
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'reports' => route('reports.index'),
                'payments' => route('payments.index'),
                'auditLogs' => route('audit-logs.index'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Domains\Reports\Services\ReportService;
use App\Domains\Reports\Services\SimplePdfReport;
use App\Models\Report;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);

        return Inertia::render('Reports/Index', [
            'data' => $this->reportService->summary(),
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'dashboard' => route('dashboard'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function dashboard(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);

        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        return Inertia::render('Reports/Dashboard', [
            'data' => $this->reportService->dashboard(
                $startDate !== '' ? $startDate : null,
                $endDate !== '' ? $endDate : null
            ),
            'filters' => [
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'index' => route('reports.index'),
                'export' => route('reports.export'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Report::class);

        $format = strtolower($request->string('format')->toString());
        if ($format !== 'csv') {
            abort(422, 'Only CSV exports are supported right now.');
        }

        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        $data = $this->reportService->dashboard(
            $startDate !== '' ? $startDate : null,
            $endDate !== '' ? $endDate : null
        );

        $filename = 'reports-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Summary']);
            foreach ($data['summary'] as $label => $value) {
                fputcsv($handle, [$label, $value]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Pending approvals', $data['approvals']['pending']]);
            fputcsv($handle, ['Pending approval cost', $data['approvals']['pendingCost']]);

            fputcsv($handle, []);
            fputcsv($handle, ['Facility costs']);
            foreach ($data['breakdowns']['facilities'] as $facility) {
                fputcsv($handle, [$facility->name, $facility->total_cost]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Vendor costs']);
            foreach ($data['breakdowns']['vendors'] as $vendor) {
                fputcsv($handle, [$vendor->name, $vendor->total_cost]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Request type costs']);
            foreach ($data['breakdowns']['types'] as $type) {
                fputcsv($handle, [$type->name, $type->total_cost]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Trend']);
            foreach ($data['trend'] as $trend) {
                fputcsv($handle, [$trend['period'], $trend['count'], $trend['total_cost']]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function adminDashboard(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);

        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        return Inertia::render('Reports/AdminDashboard', [
            'data' => $this->reportService->adminDashboard(
                $startDate !== '' ? $startDate : null,
                $endDate !== '' ? $endDate : null
            ),
            'filters' => [
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'index' => route('reports.index'),
                'export' => route('reports.admin.export'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function adminExport(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $format = strtolower($request->string('format')->toString());
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        $data = $this->reportService->adminDashboard(
            $startDate !== '' ? $startDate : null,
            $endDate !== '' ? $endDate : null
        );

        if ($format === 'pdf') {
            $lines = [
                'Admin Reports Snapshot',
                'Generated: ' . now()->toDateTimeString(),
                '',
                'Summary',
                'Users: ' . $data['summary']['users'],
                'Facilities: ' . $data['summary']['facilities'],
                'Facility Types: ' . $data['summary']['facilityTypes'],
                'Request Types: ' . $data['summary']['requestTypes'],
                'Maintenance Requests: ' . $data['summary']['maintenanceRequests'],
                'Work Orders: ' . $data['summary']['workOrders'],
                'Payments: ' . $data['summary']['payments'],
                '',
                'Costs',
                'Total: ' . $data['costs']['total'],
                'Average: ' . $data['costs']['average'],
            ];

            $pdf = SimplePdfReport::build($lines);
            $filename = 'admin-reports-' . now()->format('Ymd-His') . '.pdf';

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        if ($format !== 'csv') {
            abort(422, 'Only CSV and PDF exports are supported.');
        }

        $filename = 'admin-reports-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Summary']);
            foreach ($data['summary'] as $label => $value) {
                fputcsv($handle, [$label, $value]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Costs', 'Value']);
            fputcsv($handle, ['total', $data['costs']['total']]);
            fputcsv($handle, ['average', $data['costs']['average']]);

            fputcsv($handle, []);
            fputcsv($handle, ['Status breakdown']);
            foreach ($data['statusBreakdown'] as $group => $breakdown) {
                fputcsv($handle, [$group]);
                foreach ($breakdown as $status => $count) {
                    fputcsv($handle, [$status, $count]);
                }
                fputcsv($handle, []);
            }

            fputcsv($handle, ['Trends']);
            foreach ($data['trends'] as $group => $entries) {
                fputcsv($handle, [$group]);
                foreach ($entries as $entry) {
                    fputcsv($handle, $entry);
                }
                fputcsv($handle, []);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

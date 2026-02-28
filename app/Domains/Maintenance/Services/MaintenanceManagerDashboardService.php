<?php

namespace App\Domains\Maintenance\Services;

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\Carbon;

class MaintenanceManagerDashboardService
{
    public function summary(User $user): array
    {
        return [
            'metrics' => $this->metrics(),
            'queues' => $this->queues(),
        ];
    }

    protected function metrics(): array
    {
        return [
            'open_requests' => MaintenanceRequest::query()
            ->whereIn('status', MaintenanceStatus::active())
                ->count(),
            'work_orders_in_flight' => WorkOrder::query()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count(),
            'pending_payments' => Payment::query()
                ->where('status', 'pending')
                ->count(),
        ];
    }

    protected function queues(): array
    {
        $pendingRequests = MaintenanceRequest::query()
            ->with(['facility', 'requestType'])
            ->whereIn('status', [
                MaintenanceStatus::Approved->value,
                MaintenanceStatus::Assigned->value,
                MaintenanceStatus::WorkOrderCreated->value,
            ])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (MaintenanceRequest $request) => [
                'id' => $request->id,
                'facility' => $request->facility?->name,
                'request_type' => $request->requestType?->name,
                'description' => $request->description,
                'cost' => $request->cost,
                'created_at' => $request->created_at?->toDateString(),
            ]);

        $overdueWorkOrders = WorkOrder::query()
            ->with(['vendor', 'maintenanceRequest.facility'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('scheduled_date')
            ->whereDate('scheduled_date', '<', Carbon::today())
            ->orderBy('scheduled_date')
            ->take(5)
            ->get()
            ->map(fn (WorkOrder $workOrder) => [
                'id' => $workOrder->id,
                'facility' => $workOrder->maintenanceRequest?->facility?->name,
                'vendor' => $workOrder->vendor?->name,
                'scheduled_date' => $workOrder->scheduled_date?->toDateString(),
                'estimated_cost' => $workOrder->estimated_cost,
                'status' => $workOrder->status,
            ]);

        $pendingPayments = Payment::query()
            ->with(['maintenanceRequest.facility'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'maintenance_request_id' => $payment->maintenance_request_id,
                'facility' => $payment->maintenanceRequest?->facility?->name,
                'cost' => $payment->cost,
                'amount_payed' => $payment->amount_payed,
                'status' => $payment->status,
            ]);

        return [
            'pendingRequests' => $pendingRequests,
            'overdueWorkOrders' => $overdueWorkOrders,
            'pendingPayments' => $pendingPayments,
        ];
    }
}

<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Actions\SendUsersNotificationAction;
use App\Domains\Notifications\DTOs\UserNotificationData;
use App\Models\Facility;
use App\Models\Inspection;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Todo;
use App\Models\User;
use App\Models\WorkOrder;

class OperationalNotificationService
{
    public function __construct(
        protected SendUsersNotificationAction $sendUsersNotificationAction,
        protected NotificationAudienceService $notificationAudienceService
    ) {}

    public function maintenanceRequestCreated(MaintenanceRequest $request): void
    {
        $request->loadMissing('facility');

        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->maintenanceRequestAudience($request),
            event: 'maintenance_request.created',
            title: 'New maintenance request',
            body: $this->requestLabel($request).' was submitted for '.$request->facility?->name.'.',
            actionUrl: route('maintenance.show', $request),
            meta: [
                'maintenance_request_id' => $request->id,
                'status' => $request->status,
                'priority' => $request->priority,
            ],
            category: 'maintenance',
            severity: UserNotificationData::SeverityInfo,
        );
    }

    public function maintenanceRequestUpdated(MaintenanceRequest $request): void
    {
        $request->loadMissing('facility');

        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->maintenanceRequestAudience($request),
            event: 'maintenance_request.updated',
            title: 'Maintenance request updated',
            body: $this->requestLabel($request).' was updated for '.$request->facility?->name.'.',
            actionUrl: route('maintenance.show', $request),
            meta: [
                'maintenance_request_id' => $request->id,
                'status' => $request->status,
                'priority' => $request->priority,
            ],
            category: 'maintenance',
            severity: UserNotificationData::SeverityInfo,
        );
    }

    public function maintenanceRequestApproved(MaintenanceRequest $request): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->maintenanceRequestStakeholders($request),
            event: 'maintenance_request.approved',
            title: 'Maintenance request approved',
            body: $this->requestLabel($request).' is now '.$request->status.'.',
            actionUrl: route('maintenance.show', $request),
            meta: [
                'maintenance_request_id' => $request->id,
                'status' => $request->status,
            ],
            category: 'maintenance',
            severity: UserNotificationData::SeveritySuccess,
        );
    }

    public function maintenanceRequestRejected(MaintenanceRequest $request, string $reason): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->maintenanceRequestStakeholders($request),
            event: 'maintenance_request.rejected',
            title: 'Maintenance request rejected',
            body: $this->requestLabel($request).' was rejected. Reason: '.$reason,
            actionUrl: route('maintenance.show', $request),
            meta: [
                'maintenance_request_id' => $request->id,
                'status' => $request->status,
                'rejection_reason' => $reason,
            ],
            category: 'maintenance',
            severity: UserNotificationData::SeverityDanger,
        );
    }

    public function maintenanceRequestStarted(MaintenanceRequest $request): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->maintenanceRequestStakeholders($request),
            event: 'maintenance_request.started',
            title: 'Maintenance started',
            body: $this->requestLabel($request).' is now in progress.',
            actionUrl: route('maintenance.show', $request),
            meta: [
                'maintenance_request_id' => $request->id,
                'status' => $request->status,
            ],
            category: 'maintenance',
            severity: UserNotificationData::SeverityInfo,
        );
    }

    public function maintenanceRequestCompleted(MaintenanceRequest $request): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->maintenanceRequestStakeholders($request),
            event: 'maintenance_request.completed',
            title: 'Maintenance completed',
            body: $this->requestLabel($request).' is awaiting payment.',
            actionUrl: route('maintenance.show', $request),
            meta: [
                'maintenance_request_id' => $request->id,
                'status' => $request->status,
            ],
            category: 'maintenance',
            severity: UserNotificationData::SeveritySuccess,
        );
    }

    public function maintenanceRequestClosed(MaintenanceRequest $request): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->maintenanceRequestStakeholders($request),
            event: 'maintenance_request.closed',
            title: 'Maintenance closed',
            body: $this->requestLabel($request).' has been closed.',
            actionUrl: route('maintenance.show', $request),
            meta: [
                'maintenance_request_id' => $request->id,
                'status' => $request->status,
            ],
            category: 'maintenance',
            severity: UserNotificationData::SeveritySuccess,
        );
    }

    public function workOrderCreated(WorkOrder $workOrder): void
    {
        $workOrder->loadMissing('maintenanceRequest.facility', 'vendor');

        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->workOrderAudience($workOrder),
            event: 'work_order.created',
            title: 'Work order created',
            body: 'Work order #'.$workOrder->id.' was created for '.$workOrder->maintenanceRequest?->facility?->name.'.',
            actionUrl: $workOrder->maintenanceRequest
                ? route('maintenance.show', $workOrder->maintenanceRequest)
                : route('work-orders.show', $workOrder),
            meta: [
                'work_order_id' => $workOrder->id,
                'maintenance_request_id' => $workOrder->maintenance_request_id,
                'status' => $workOrder->status,
                'vendor_id' => $workOrder->vendor_id,
            ],
            category: 'work_orders',
            severity: UserNotificationData::SeverityInfo,
        );
    }

    public function workOrderUpdated(WorkOrder $workOrder): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->workOrderAudience($workOrder),
            event: 'work_order.updated',
            title: 'Work order updated',
            body: 'Work order #'.$workOrder->id.' is now '.$workOrder->status.'.',
            actionUrl: $workOrder->maintenanceRequest
                ? route('maintenance.show', $workOrder->maintenanceRequest)
                : route('work-orders.show', $workOrder),
            meta: [
                'work_order_id' => $workOrder->id,
                'maintenance_request_id' => $workOrder->maintenance_request_id,
                'status' => $workOrder->status,
                'vendor_id' => $workOrder->vendor_id,
            ],
            category: 'work_orders',
            severity: $workOrder->status === 'cancelled'
                ? UserNotificationData::SeverityWarning
                : UserNotificationData::SeverityInfo,
        );
    }

    public function paymentCreated(Payment $payment): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->paymentAudience($payment),
            event: 'payment.created',
            title: 'Payment approval needed',
            body: 'Payment #'.$payment->id.' is pending approval.',
            actionUrl: '/payment-approvals',
            meta: [
                'payment_id' => $payment->id,
                'maintenance_request_id' => $payment->maintenance_request_id,
                'status' => $payment->status,
                'cost' => $payment->cost,
            ],
            category: 'payments',
            severity: UserNotificationData::SeverityWarning,
        );
    }

    public function paymentApproved(Payment $payment): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->paymentAudience($payment),
            event: 'payment.approved',
            title: 'Payment approved',
            body: 'Payment #'.$payment->id.' was approved.',
            actionUrl: $payment->maintenanceRequest
                ? route('maintenance.show', $payment->maintenanceRequest)
                : route('payments.show', $payment),
            meta: [
                'payment_id' => $payment->id,
                'status' => $payment->status,
            ],
            category: 'payments',
            severity: UserNotificationData::SeveritySuccess,
        );
    }

    public function paymentRejected(Payment $payment): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->paymentAudience($payment),
            event: 'payment.rejected',
            title: 'Payment rejected',
            body: 'Payment #'.$payment->id.' was rejected.',
            actionUrl: $payment->maintenanceRequest
                ? route('maintenance.show', $payment->maintenanceRequest)
                : route('payments.show', $payment),
            meta: [
                'payment_id' => $payment->id,
                'status' => $payment->status,
            ],
            category: 'payments',
            severity: UserNotificationData::SeverityDanger,
        );
    }

    public function todoCreated(Todo $todo): void
    {
        $todo->loadMissing('facility');

        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->todoAudience($todo),
            event: 'todo.created',
            title: 'New todo assigned',
            body: 'A todo was added for '.$todo->facility?->name.'.',
            actionUrl: route('todos.edit', $todo),
            meta: [
                'todo_id' => $todo->id,
                'status' => $todo->status,
            ],
            category: 'todos',
            severity: UserNotificationData::SeverityInfo,
        );
    }

    public function todoUpdated(Todo $todo): void
    {
        $todo->loadMissing('facility');

        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->todoAudience($todo),
            event: 'todo.updated',
            title: 'Todo updated',
            body: 'A todo for '.$todo->facility?->name.' was updated.',
            actionUrl: route('todos.edit', $todo),
            meta: [
                'todo_id' => $todo->id,
                'status' => $todo->status,
            ],
            category: 'todos',
            severity: UserNotificationData::SeverityInfo,
        );
    }

    public function todoCompleted(Todo $todo): void
    {
        $todo->loadMissing('facility');

        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->todoAudience($todo),
            event: 'todo.completed',
            title: 'Todo completed',
            body: 'A todo for '.$todo->facility?->name.' was completed.',
            actionUrl: route('todos.edit', $todo),
            meta: [
                'todo_id' => $todo->id,
                'status' => $todo->status,
            ],
            category: 'todos',
            severity: UserNotificationData::SeveritySuccess,
        );
    }

    public function inspectionCreated(Inspection $inspection): void
    {
        $inspection->loadMissing('facility.manager');

        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->facilityStakeholders($inspection->facility),
            event: 'inspection.created',
            title: 'Inspection recorded',
            body: 'Inspection #'.$inspection->id.' was recorded for '.$inspection->facility?->name.'.',
            actionUrl: route('inspections.show', $inspection),
            meta: [
                'inspection_id' => $inspection->id,
                'facility_id' => $inspection->facility_id,
                'condition' => $inspection->condition,
            ],
            category: 'inspections',
            severity: $inspection->condition === 'Good'
                ? UserNotificationData::SeverityInfo
                : UserNotificationData::SeverityWarning,
        );
    }

    public function facilityCreated(Facility $facility): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: $this->notificationAudienceService->facilityStakeholders($facility),
            event: 'facility.created',
            title: 'Facility created',
            body: $facility->name.' was added to the portfolio.',
            actionUrl: route('facilities.show', $facility),
            meta: [
                'facility_id' => $facility->id,
            ],
            category: 'facilities',
            severity: UserNotificationData::SeverityInfo,
        );
    }

    public function facilityManagerChanged(Facility $facility, ?int $previousManagerId): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: array_values(array_filter([
                $previousManagerId,
                $facility->managed_by,
            ])),
            event: 'facility.assignment_changed',
            title: 'Facility assignment updated',
            body: $facility->name.' has a new facility manager assignment.',
            actionUrl: route('facilities.show', $facility),
            meta: [
                'facility_id' => $facility->id,
                'managed_by' => $facility->managed_by,
            ],
            category: 'facilities',
            severity: UserNotificationData::SeverityWarning,
        );
    }

    public function directReportAssigned(User $report, User $manager): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: [$report->id, $manager->id],
            event: 'user.manager_assigned',
            title: 'Manager assignment updated',
            body: $report->name.' now reports to '.$manager->name.'.',
            actionUrl: route('dashboard'),
            meta: [
                'user_id' => $report->id,
                'manager_id' => $manager->id,
            ],
            category: 'users',
            severity: UserNotificationData::SeverityInfo,
        );
    }

    public function directReportUnassigned(User $report): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: [$report->id],
            event: 'user.manager_unassigned',
            title: 'Manager assignment removed',
            body: $report->name.' no longer has a supervising manager.',
            actionUrl: route('dashboard'),
            meta: [
                'user_id' => $report->id,
            ],
            category: 'users',
            severity: UserNotificationData::SeverityWarning,
        );
    }

    public function managerAccessGranted(User $manager): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: [$manager->id],
            event: 'user.manager_access_granted',
            title: 'Maintenance manager access granted',
            body: 'You can now access maintenance manager workflows.',
            actionUrl: route('dashboard'),
            meta: [
                'user_id' => $manager->id,
            ],
            category: 'users',
            severity: UserNotificationData::SeveritySuccess,
        );
    }

    public function managerAccessRevoked(User $manager): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: [$manager->id],
            event: 'user.manager_access_revoked',
            title: 'Maintenance manager access revoked',
            body: 'Your maintenance manager access has been removed.',
            actionUrl: route('dashboard'),
            meta: [
                'user_id' => $manager->id,
            ],
            category: 'users',
            severity: UserNotificationData::SeverityWarning,
        );
    }

    public function userActivated(User $user): void
    {
        $this->sendUsersNotificationAction->execute(
            userIds: [$user->id],
            event: 'user.activated',
            title: 'Account activated',
            body: 'Your account is active again.',
            actionUrl: route('dashboard'),
            meta: [
                'user_id' => $user->id,
            ],
            category: 'users',
            severity: UserNotificationData::SeveritySuccess,
            excludeActor: false,
        );
    }

    protected function requestLabel(MaintenanceRequest $request): string
    {
        return 'Request #'.$request->id;
    }
}

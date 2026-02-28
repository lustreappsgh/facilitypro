<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Assigned = 'assigned';
    case WorkOrderCreated = 'work_order_created';
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case CompletedPendingPayment = 'completed_pending_payment';
    case Paid = 'paid';
    case Closed = 'closed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Assigned => 'Assigned',
            self::WorkOrderCreated => 'Work Order Created',
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::CompletedPendingPayment => 'Completed - Pending Payment',
            self::Paid => 'Paid',
            self::Closed => 'Closed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public static function requesterEditable(): array
    {
        return [
            self::Submitted->value,
            self::Pending->value,
        ];
    }

    public static function approvalQueue(): array
    {
        return [
            self::Submitted->value,
            self::Pending->value,
        ];
    }

    public static function assignmentReady(): array
    {
        return [
            self::Approved->value,
            self::Assigned->value,
            self::WorkOrderCreated->value,
            self::InProgress->value,
        ];
    }

    public static function active(): array
    {
        return [
            self::Submitted->value,
            self::Pending->value,
            self::Approved->value,
            self::Assigned->value,
            self::WorkOrderCreated->value,
            self::InProgress->value,
            self::CompletedPendingPayment->value,
            self::Paid->value,
        ];
    }

    public static function terminal(): array
    {
        return [
            self::Rejected->value,
            self::Closed->value,
            self::Completed->value,
            self::Cancelled->value,
        ];
    }
}

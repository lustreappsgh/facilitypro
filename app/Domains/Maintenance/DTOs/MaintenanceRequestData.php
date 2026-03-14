<?php

namespace App\Domains\Maintenance\DTOs;

use App\Models\MaintenanceRequest;

class MaintenanceRequestData
{
    public function __construct(
        public int $facility_id,
        public int $request_type_id,
        public ?string $description,
        public ?int $cost,
        public string $week_start,
        public ?string $status,
        public ?string $submission_route,
        public int $requested_by,
    ) {}

    public static function fromRequest(
        array $data,
        ?string $defaultSubmissionRoute = MaintenanceRequest::SubmissionRouteMaintenanceManager
    ): self {
        $weekStart = isset($data['week_start']) && $data['week_start']
            ? (string) $data['week_start']
            : now()->startOfWeek(\Carbon\Carbon::MONDAY)->addWeek()->toDateString();

        $submissionRoute = $data['submission_route'] ?? $defaultSubmissionRoute;

        return new self(
            facility_id: (int) $data['facility_id'],
            request_type_id: (int) $data['request_type_id'],
            description: $data['description'] ?? null,
            cost: isset($data['cost']) ? (int) $data['cost'] : null,
            week_start: $weekStart,
            status: $data['status'] ?? null,
            submission_route: $submissionRoute,
            requested_by: $data['requested_by'] ?? auth()->id(),
        );
    }

    public function toArray(): array
    {
        $payload = [
            'facility_id' => $this->facility_id,
            'request_type_id' => $this->request_type_id,
            'description' => $this->description,
            'cost' => $this->cost,
            'week_start' => $this->week_start,
            'requested_by' => $this->requested_by,
        ];

        if ($this->status !== null) {
            $payload['status'] = $this->status;
        }

        if ($this->submission_route !== null) {
            $payload['submission_route'] = $this->submission_route;
        }

        return $payload;
    }
}

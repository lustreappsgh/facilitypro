<?php

namespace App\Models;

use App\Support\TextNormalizer;
use App\Traits\ResolvesMaintenanceScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class MaintenanceRequest extends BaseModel
{
    use HasFactory;
    use ResolvesMaintenanceScope;

    public const PriorityLow = 'low';

    public const PriorityMedium = 'medium';

    public const PriorityHigh = 'high';

    public const SubmissionRouteMaintenanceManager = 'maintenance_manager';

    public const SubmissionRouteAdmin = 'admin';

    protected $casts = [
        'created_at' => 'date:M j, Y',
        'week_start' => 'date:M j, Y',
    ];

    protected $fillable = [
        'facility_id',
        'request_type_id',
        'priority',
        'description',
        'rejection_reason',
        'cost',
        'status',
        'submission_route',
        'requested_by',
        'week_start',
    ];

    protected $appends = [
        'month_week',
    ];

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = TextNormalizer::fixMojibake($value);
    }

    public function getMonthWeekAttribute(): string
    {
        $reference = $this->week_start ?? $this->created_at;
        $startOfWeek = $reference->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $reference->copy()->endOfWeek(Carbon::SATURDAY);

        $weekNumber = $startOfWeek->weekOfMonth;
        $monthName = $startOfWeek->format('F');

        return $monthName.' wk '.$weekNumber.' ('.$startOfWeek->format('M d').' - '.$endOfWeek->format('M d').')';
    }

    public static function submissionRoutes(): array
    {
        return [
            self::SubmissionRouteMaintenanceManager,
            self::SubmissionRouteAdmin,
        ];
    }

    public static function priorities(): array
    {
        return [
            self::PriorityLow,
            self::PriorityMedium,
            self::PriorityHigh,
        ];
    }

    public static function prioritySortCase(string $column = 'priority'): string
    {
        return "CASE {$column}
            WHEN '".self::PriorityHigh."' THEN 0
            WHEN '".self::PriorityMedium."' THEN 1
            WHEN '".self::PriorityLow."' THEN 2
            ELSE 3
        END";
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    public function requestType(): BelongsTo
    {
        return $this->belongsTo(RequestType::class);
    }

    public function scopeUserRequests($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        return $query
            ->maintenanceScope($user)
            ->with(['facility', 'requestedBy', 'requestType', 'payments']);
    }

    public function scopeMaintenanceScope($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('users.manage')) {
            return $query;
        }

        if ($user->can('maintenance.manage_all')) {
            $scopedQuery = $query->where(function ($builder) use ($user) {
                $builder->whereHas('facility', fn ($q) => $q->whereIn(
                    'managed_by',
                    $this->maintenanceScopeManagerIds($user)
                ))->orWhereIn('requested_by', $this->maintenanceScopeManagerIds($user));
            });

            $scopedQuery->where('submission_route', self::SubmissionRouteMaintenanceManager);

            if ($this->shouldRestrictRequestTypes($user)) {
                $allowedIds = $this->allowedRequestTypeIds($user);
                if ($allowedIds !== []) {
                    $scopedQuery->whereIn('request_type_id', $allowedIds);
                }
            }

            return $scopedQuery;
        }

        if (
            $user->can('maintenance_requests.view')
            || $user->can('maintenance.start')
            || $user->can('maintenance.review')
            || $user->can('work_orders.create')
            || $user->can('maintenance.complete')
            || $user->can('maintenance.close')
        ) {
            $scopedQuery = $query->where(function ($builder) use ($user) {
                $builder->whereHas('facility', fn ($q) => $q->whereIn(
                    'managed_by',
                    $this->maintenanceScopeManagerIds($user)
                ))->orWhereIn('requested_by', $this->maintenanceScopeManagerIds($user));
            });

            $scopedQuery->where('submission_route', self::SubmissionRouteMaintenanceManager);

            if ($this->shouldRestrictRequestTypes($user)) {
                $allowedIds = $this->allowedRequestTypeIds($user);
                if ($allowedIds === []) {
                    return $scopedQuery->whereRaw('1 = 0');
                }

                $scopedQuery->whereIn('request_type_id', $allowedIds);
            }

            return $scopedQuery;
        }

        if ($user->can('maintenance.view')) {
            return $query->where(function ($builder) use ($user) {
                $builder->where('requested_by', $user->id)
                    ->orWhereHas('facility', fn ($facilityQuery) => $facilityQuery->where('managed_by', $user->id));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['status'] ?? null, function ($query, $status) {
            $query->where(function ($query) use ($status) {
                if (! $status === 'approved' || ! $status === 'pending') {
                    return $query;
                } else {
                    $query->where('status', 'like', '%'.$status.'%');
                }
            });
        })->when($filters['role'] ?? null, function ($query, $role) {
            $query->whereRole($role);
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });
    }

    public function scopeReviewableBy($query, User $user)
    {
        $query = $query->maintenanceScope($user);
        $query->where('requested_by', '!=', $user->id);

        if ($user->can('maintenance.manage_all') && ! $user->can('users.manage')) {
            $reviewableIds = $this->managerReviewableRequestTypeIds($user);

            if ($reviewableIds === null) {
                return $query;
            }

            if ($reviewableIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereIn('request_type_id', $reviewableIds);
        }

        if ($this->shouldRestrictRequestTypes($user)) {
            $allowedIds = $this->allowedRequestTypeIds($user);
            if ($allowedIds !== []) {
                $query->whereIn('request_type_id', $allowedIds);
            }
        }

        return $query;
    }

    protected function shouldRestrictRequestTypes(User $user): bool
    {
        return ! $user->can('users.manage')
            && $user->can('maintenance.start');
    }

    protected function allowedRequestTypeIds(User $user): array
    {
        $roleIds = $user->roles()->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return [];
        }

        return DB::table('maintenance_request_type_role')
            ->whereIn('role_id', $roleIds)
            ->pluck('request_type_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function canBeReviewedBy(User $user, string $action): bool
    {
        if ($user->can('users.manage')) {
            return true;
        }

        if ($this->requested_by === $user->id) {
            return false;
        }

        if (! self::query()->maintenanceScope($user)->whereKey($this->id)->exists()) {
            return false;
        }

        if ($user->can('maintenance.manage_all')) {
            return $this->managerCanReviewRequestType($user, $action);
        }

        if ($this->shouldRestrictRequestTypes($user)) {
            return in_array($this->request_type_id, $this->allowedRequestTypeIds($user), true);
        }

        return true;
    }

    public function managerCanReviewRequestType(User $user, string $action): bool
    {
        $permissions = $this->managerRequestTypePermissions($user);

        if ($permissions === null) {
            return true;
        }

        $permission = $permissions[$this->request_type_id] ?? null;
        if (! $permission) {
            return false;
        }

        return match ($action) {
            'approve' => (bool) $permission['can_approve'],
            'reject' => (bool) $permission['can_reject'],
            default => (bool) $permission['can_approve'] || (bool) $permission['can_reject'],
        };
    }

    public function managerReviewableRequestTypeIds(User $user): ?array
    {
        $permissions = $this->managerRequestTypePermissions($user);

        if ($permissions === null) {
            return null;
        }

        return collect($permissions)
            ->filter(fn (array $permission) => $permission['can_approve'] || $permission['can_reject'])
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function managerRequestTypePermissions(User $user): ?array
    {
        if (! $user->can('maintenance.manage_all') || $user->can('users.manage')) {
            return null;
        }

        $roleIds = $user->roles()->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return null;
        }

        $permissions = DB::table('maintenance_request_type_role')
            ->whereIn('role_id', $roleIds)
            ->get(['request_type_id', 'can_approve', 'can_reject']);

        if ($permissions->isEmpty()) {
            return null;
        }

        return $permissions
            ->groupBy('request_type_id')
            ->map(fn ($rows) => [
                'can_approve' => $rows->contains(fn ($row) => (bool) $row->can_approve),
                'can_reject' => $rows->contains(fn ($row) => (bool) $row->can_reject),
            ])
            ->all();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}

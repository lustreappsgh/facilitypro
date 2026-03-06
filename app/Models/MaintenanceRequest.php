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

    protected $casts = [
        'created_at' => 'date:M j, Y',
        'week_start' => 'date:M j, Y',
    ];

    protected $fillable = ['facility_id', 'request_type_id', 'description', 'cost', 'status', 'requested_by', 'week_start'];

    protected $appends = [
        'month_week',
    ];

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = TextNormalizer::fixMojibake($value);
    }

    public function getMonthWeekAttribute()
    {
        $reference = $this->week_start ?? $this->created_at;
        $startOfWeek = $reference->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $reference->copy()->endOfWeek(Carbon::SATURDAY);

        $weekNumber = $startOfWeek->weekOfMonth;
        $monthName = $startOfWeek->format('F');

        return $monthName.' wk '.$weekNumber.' ('.$startOfWeek->format('M d').' - '.$endOfWeek->format('M d').')';
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
            $scopedQuery = $query->whereHas('facility', fn ($q) => $q->whereIn(
                'managed_by',
                $this->maintenanceScopeManagerIds($user)
            ));

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
            $scopedQuery = $query
                ->whereHas('facility', fn ($q) => $q->whereIn(
                    'managed_by',
                    $this->maintenanceScopeManagerIds($user)
                ));

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
            && (
                $user->can('maintenance.start')
                || $user->can('maintenance.manage_all')
            );
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}

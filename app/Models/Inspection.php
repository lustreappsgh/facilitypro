<?php

namespace App\Models;

use App\Support\TextNormalizer;
use App\Traits\ResolvesMaintenanceScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends BaseModel
{
    use HasFactory;
    use ResolvesMaintenanceScope;

    protected $fillable = [
        'inspection_date',
        'facility_id',
        'condition',
        'comments',
        'image',
        'added_by',
    ];

    protected $casts = [
        'inspection_date' => 'date:M j, Y',
        'created_at' => 'date:M j, Y',
    ];

    protected $appends = [
        'month_week',
    ];

    public function setCommentsAttribute(?string $value): void
    {
        $this->attributes['comments'] = TextNormalizer::fixMojibake($value);
    }

    public function getMonthWeekAttribute(): string
    {
        $startOfWeek = $this->created_at->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $this->created_at->copy()->endOfWeek(Carbon::SATURDAY);

        $weekNumber = $startOfWeek->weekOfMonth;
        $monthName = $startOfWeek->format('F');

        return $monthName.' wk '.$weekNumber.' ('.$startOfWeek->format('M d').' - '.$endOfWeek->format('M d').')';
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function scopeUserVisible($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('users.manage')) {
            return $query->with(['facility', 'addedBy']);
        }

        if ($user->can('maintenance.manage_all')) {
            return $query->with(['facility', 'addedBy'])
                ->where(function ($builder) use ($user) {
                    $builder->whereIn('added_by', $this->maintenanceScopeManagerIds($user))
                        ->orWhereIn('facility_id', $this->maintenanceScopeFacilityIds($user));
                });
        }

        return $query->with(['facility', 'addedBy'])
            ->where(function ($builder) use ($user) {
                $builder->where('added_by', $user->id)
                    ->orWhereHas('facility', fn ($facilityQuery) => $facilityQuery->where('managed_by', $user->id));
            });
    }
}

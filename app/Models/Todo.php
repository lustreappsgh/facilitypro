<?php

namespace App\Models;

use App\Support\TextNormalizer;
use App\Traits\ResolvesMaintenanceScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends BaseModel
{
    use HasFactory;
    use ResolvesMaintenanceScope;

    public $fillable = [
        'user_id',
        'facility_id',
        'description',
        'completed_at',
        'status',
        'week_start',
    ];

    public $appends = [
        'month_week',
    ];

    protected $casts = [
        'completed_at' => 'datetime:M j, Y',
        'week_start' => 'date:M j, Y',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
            return $query;
        }

        if ($user->can('maintenance.manage_all')) {
            return $query->whereIn('user_id', $this->maintenanceScopeManagerIds($user));
        }

        return $query->where('user_id', $user->id);
    }
}

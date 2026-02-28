<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_date',
        'facility_id',
        'condition',
        'comments',
        'image',
        'added_by',
    ];

    protected $casts = [
        'inspection_date' => 'datetime:Y-m-d',
        'created_at' => 'date:Y-m-d',
    ];


    protected $appends = [
        'month_week',
    ];

    public function setCommentsAttribute(?string $value): void
    {
        $this->attributes['comments'] = TextNormalizer::fixMojibake($value);
    }

    public function getMonthWeekAttribute()
    {

        $startOfWeek = $this->created_at->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $this->created_at->copy()->endOfWeek(Carbon::SATURDAY);

        $weekNumber = $startOfWeek->weekOfMonth;
        $monthName = $startOfWeek->format('F');

        return $monthName . ' wk ' . $weekNumber . ' (' . $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d') . ')';
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

        return $query->with(['facility', 'addedBy'])
            ->where(function ($builder) use ($user) {
                $builder->where('added_by', $user->id)
                    ->orWhereHas('facility', fn ($facilityQuery) => $facilityQuery->where('managed_by', $user->id));
            });
    }
}

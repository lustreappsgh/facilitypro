<?php

namespace App\Models;

use App\Models\User;
use App\Support\TextNormalizer;
use App\Traits\ResolvesMaintenanceScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Facility extends BaseModel
{
    use HasFactory;
    use ResolvesMaintenanceScope;
    public $fillable = [
        'name',
        'facility_type_id',
        'parent_id',
        'condition',
        'managed_by',
    ];

    public function facilityType()
    {
        return $this->belongsTo(FacilityType::class);
    }
    public function parent()
    {
        return $this->belongsTo(Facility::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Facility::class, 'parent_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'managed_by');
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = TextNormalizer::fixMojibake($value);
    }

    public function scopeUserFacilities($query, $parentFacility = null, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! $user->can('facilities.view') && ! $user->can('facilities.update')) {
            return $query->whereRaw('1 = 0');
        }

        if (! $user->can('users.manage')) {
            if ($user->can('maintenance.manage_all')) {
                if ($parentFacility === 'Campus Paid Job') {
                    $query->where('name', 'Campus Paid Jobs');
                } else {
                    $query->whereIn('managed_by', $this->maintenanceScopeManagerIds($user));
                }
            } else {
                if ($parentFacility === 'Campus Paid Job') {
                    $query->where('name', 'Campus Paid Jobs');
                } else {
                    $query->where('managed_by', $user->id);
                }
            }
        }

        if ($parentFacility && $parentFacility !== 'Campus Paid Job') {
            $query->whereHas('parent', fn ($q) => $q->where('name', $parentFacility));
        }

        return $query;
    }

    public function scopeMaintenanceFacilities($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('users.manage')) {
            return $query;
        }

        if ($user->can('maintenance.manage_all')) {
            return $query->whereIn('managed_by', $this->maintenanceScopeManagerIds($user));
        }

        return $query->whereIn('managed_by', $this->maintenanceScopeManagerIds($user));
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }
    public function inspections()
    {
        return $this->hasMany(Inspection::class);
    }
}

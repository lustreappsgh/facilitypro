<?php

namespace App\Models;

use App\Traits\ResolvesMaintenanceScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkOrder extends Model
{
    use HasFactory;
    use ResolvesMaintenanceScope;

    protected $fillable = [
        'maintenance_request_id', 'vendor_id', 'assigned_date',
        'scheduled_date', 'completed_date', 'estimated_cost',
        'actual_cost', 'status', 'assigned_by',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeUserVisible($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('maintenance.manage_all')) {
            return $query;
        }

        return $query->whereHas('maintenanceRequest', fn ($requestQuery) => $requestQuery->maintenanceScope($user));
    }
}

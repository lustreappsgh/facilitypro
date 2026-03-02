<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends BaseModel
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'service_type', 'status'];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }
}

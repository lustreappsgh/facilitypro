<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'service_type', 'status'];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }
}

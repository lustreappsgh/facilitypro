<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class PaymentApproval extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'approver_id',
        'approved_by',
        'approval_level',
        'status',
        'comments'
    ];

    public static function actorColumn(): string
    {
        return Schema::hasColumn('payment_approvals', 'approver_id')
            ? 'approver_id'
            : 'approved_by';
    }

    public static function hasApprovalLevelColumn(): bool
    {
        return Schema::hasColumn('payment_approvals', 'approval_level');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, static::actorColumn());
    }
}

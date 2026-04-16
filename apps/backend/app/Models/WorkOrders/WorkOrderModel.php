<?php

namespace App\Models\WorkOrders;

use App\Models\EntityModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderModel extends Model
{
    protected $table = 'work_orders';

    protected $fillable = [
        'number',
        'client_id',
        'date',
        'status',
        'description',
        'total_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItemModel::class, 'work_order_id');
    }
}

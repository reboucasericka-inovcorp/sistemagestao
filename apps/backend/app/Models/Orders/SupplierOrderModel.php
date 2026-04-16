<?php

namespace App\Models\Orders;

use App\Models\EntityModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierOrderModel extends Model
{
    protected $table = 'supplier_orders';

    protected $fillable = [
        'number',
        'supplier_id',
        'order_date',
        'status',
        'total_amount',
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierOrderItemModel::class, 'supplier_order_id');
    }
}

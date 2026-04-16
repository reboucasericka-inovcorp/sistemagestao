<?php

namespace App\Models\Orders;

use App\Models\EntityModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientOrderModel extends Model
{
    protected $table = 'client_orders';

    protected $fillable = [
        'number',
        'client_id',
        'order_date',
        'status',
        'total_amount',
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClientOrderItemModel::class, 'client_order_id');
    }
}

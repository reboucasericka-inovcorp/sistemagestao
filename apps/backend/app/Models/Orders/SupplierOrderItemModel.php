<?php

namespace App\Models\Orders;

use App\Models\Settings\ArticleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierOrderItemModel extends Model
{
    protected $table = 'supplier_order_items';

    protected $fillable = [
        'supplier_order_id',
        'article_id',
        'quantity',
        'cost_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SupplierOrderModel::class, 'supplier_order_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(ArticleModel::class, 'article_id');
    }
}

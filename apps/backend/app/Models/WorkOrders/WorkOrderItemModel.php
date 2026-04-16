<?php

namespace App\Models\WorkOrders;

use App\Models\Settings\ArticleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItemModel extends Model
{
    protected $table = 'work_order_items';

    protected $fillable = [
        'work_order_id',
        'article_id',
        'quantity',
        'price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrderModel::class, 'work_order_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(ArticleModel::class, 'article_id');
    }
}

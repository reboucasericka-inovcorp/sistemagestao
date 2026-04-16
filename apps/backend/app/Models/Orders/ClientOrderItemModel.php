<?php

namespace App\Models\Orders;

use App\Models\EntityModel;
use App\Models\Settings\ArticleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientOrderItemModel extends Model
{
    protected $table = 'client_order_items';

    protected $fillable = [
        'client_order_id',
        'article_id',
        'supplier_id',
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
        return $this->belongsTo(ClientOrderModel::class, 'client_order_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(ArticleModel::class, 'article_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'supplier_id');
    }
}

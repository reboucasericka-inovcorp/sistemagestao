<?php

namespace App\Models\Proposals;

use App\Models\EntityModel;
use App\Models\Settings\ArticleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalItemModel extends Model
{
    protected $table = 'proposal_items';

    protected $fillable = [
        'proposal_id',
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

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ProposalModel::class, 'proposal_id');
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

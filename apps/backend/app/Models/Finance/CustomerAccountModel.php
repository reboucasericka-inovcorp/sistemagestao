<?php

namespace App\Models\Finance;

use App\Models\EntityModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerAccountModel extends Model
{
    protected $table = 'customer_accounts';

    protected $fillable = [
        'entity_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function scopeByEntity(Builder $query, int $entityId): Builder
    {
        return $query->where('entity_id', $entityId);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'entity_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CustomerAccountMovementModel::class, 'customer_account_id');
    }
}

<?php

namespace App\Models\Proposals;

use App\Models\EntityModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProposalModel extends Model
{
    protected $table = 'proposals';

    protected $fillable = [
        'number',
        'proposal_date',
        'valid_until',
        'client_id',
        'status',
        'total_amount',
    ];

    protected $casts = [
        'proposal_date' => 'date',
        'valid_until' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItemModel::class, 'proposal_id');
    }
}

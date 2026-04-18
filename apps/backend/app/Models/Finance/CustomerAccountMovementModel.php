<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAccountMovementModel extends Model
{
    protected $table = 'customer_account_movements';

    protected $fillable = [
        'customer_account_id',
        'type',
        'amount',
        'description',
        'date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccountModel::class, 'customer_account_id');
    }
}

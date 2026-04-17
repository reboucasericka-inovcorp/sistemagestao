<?php

namespace App\Models\Finance;

use App\Models\DigitalFileModel;
use App\Models\EntityModel;
use App\Models\Orders\SupplierOrderModel;
use Database\Factories\SupplierInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupplierInvoiceModel extends Model
{
    use HasFactory;

    protected $table = 'supplier_invoices';

    protected $fillable = [
        'number',
        'invoice_date',
        'due_date',
        'supplier_id',
        'supplier_order_id',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending_payment');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'supplier_id');
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrderModel::class, 'supplier_order_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(DigitalFileModel::class, 'fileable');
    }

    protected static function newFactory(): SupplierInvoiceFactory
    {
        return SupplierInvoiceFactory::new();
    }
}

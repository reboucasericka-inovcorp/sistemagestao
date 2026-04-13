<?php

namespace App\Models\Settings;

use App\Models\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleModel extends Model
{
    use HasActivityLog;

    protected $table = 'articles';

    protected $fillable = [
        'reference',
        'name',
        'description',
        'price',
        'vat_id',
        'photo_path',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function vat(): BelongsTo
    {
        return $this->belongsTo(VatModel::class, 'vat_id');
    }

    protected function activityLogName(): string
    {
        return 'articles';
    }
}

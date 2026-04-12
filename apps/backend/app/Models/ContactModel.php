<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactModel extends Model
{
    protected $table = 'contacts';

    protected $fillable = [
        'entity_id',
        'contact_function_id',
        'name',
        'email',
        'phone',
        'mobile',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'entity_id');
    }

    public function contactFunction(): BelongsTo
    {
        return $this->belongsTo(ContactFunctionModel::class, 'contact_function_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity(Builder $query, int $entityId): Builder
    {
        return $query->where('entity_id', $entityId);
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value ? strtolower($value) : null;
    }
}

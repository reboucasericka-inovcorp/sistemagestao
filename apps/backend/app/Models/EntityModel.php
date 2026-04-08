<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EntityModel extends Model
{
    protected $table = 'entities';

    protected $fillable = [
        'number',
        'nif',
        'name',
        'address',
        'postal_code',
        'city',
        'country',
        'phone',
        'mobile',
        'website',
        'email',
        'gdpr_consent',
        'is_active',
        'is_client',
        'is_supplier',
        'notes',
    ];

    protected $casts = [
        'gdpr_consent' => 'boolean',
        'is_active' => 'boolean',
        'is_client' => 'boolean',
        'is_supplier' => 'boolean',
    ];

    protected $appends = ['type'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeClients(Builder $query): Builder
    {
        return $query->where('is_client', true);
    }

    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->where('is_supplier', true);
    }

    public function getTypeAttribute(): string
    {
        return match (true) {
            $this->is_client && $this->is_supplier => 'both',
            $this->is_client => 'client',
            $this->is_supplier => 'supplier',
            default => 'none',
        };
    }

    public function setNifAttribute(?string $value): void
    {
        $this->attributes['nif'] = preg_replace('/\D+/', '', (string) $value);
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value ? strtolower($value) : null;
    }
}

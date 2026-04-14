<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLog;
use App\Models\Settings\CountryModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityModel extends Model
{
    use HasActivityLog;

    protected $table = 'entities';

    protected $fillable = [
        'type',
        'number',
        'nif',
        'name',
        'address',
        'postal_code',
        'city',
        'country_id',
        'phone',
        'mobile',
        'website',
        'email',
        'gdpr_consent',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'gdpr_consent' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeClients(Builder $query): Builder
    {
        return $query->whereIn('type', ['client', 'both']);
    }

    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->whereIn('type', ['supplier', 'both']);
    }

    public function setNifAttribute(?string $value): void
    {
        $this->attributes['nif'] = preg_replace('/\D+/', '', (string) $value);
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value ? strtolower($value) : null;
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(CountryModel::class, 'country_id');
    }

    protected function activityLogName(): string
    {
        return 'entities';
    }
}

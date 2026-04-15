<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLog;
use App\Models\Settings\ContactFunctionModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactModel extends Model
{
    use HasActivityLog;

    protected $table = 'contacts';

    protected $fillable = [
        'number',
        'entity_id',
        'first_name',
        'last_name',
        'contact_function_id',
        'email',
        'phone',
        'mobile',
        'rgpd_consent',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'rgpd_consent' => 'boolean',
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

    public function getFullNameAttribute(): string
    {
        return trim(sprintf('%s %s', (string) $this->first_name, (string) $this->last_name));
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

    protected function activityLogName(): string
    {
        return 'contacts';
    }
}

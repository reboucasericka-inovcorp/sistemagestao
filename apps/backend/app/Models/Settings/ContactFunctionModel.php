<?php

namespace App\Models\Settings;

use App\Models\Concerns\HasActivityLog;
use App\Models\ContactModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactFunctionModel extends Model
{
    use HasActivityLog;

    protected $table = 'contact_functions';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ContactModel::class, 'contact_function_id');
    }

    protected function activityLogName(): string
    {
        return 'contact-functions';
    }
}

<?php

namespace App\Models\Access;

use App\Models\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RoleModel extends Role
{
    use HasActivityLog;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'guard_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function activityLogName(): string
    {
        return 'roles';
    }
}

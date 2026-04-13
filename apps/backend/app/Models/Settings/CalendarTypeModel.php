<?php

namespace App\Models\Settings;

use App\Models\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CalendarTypeModel extends Model
{
    use HasActivityLog;

    protected $table = 'calendar_types';

    protected $fillable = [
        'name',
        'color',
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
        return 'calendar-types';
    }
}

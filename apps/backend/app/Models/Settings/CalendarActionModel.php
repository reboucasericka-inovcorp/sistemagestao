<?php

namespace App\Models\Settings;

use App\Models\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarActionModel extends Model
{
    use HasActivityLog;

    protected $table = 'calendar_actions';

    protected $fillable = [
        'name',
        'calendar_type_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function calendarType(): BelongsTo
    {
        return $this->belongsTo(CalendarTypeModel::class, 'calendar_type_id');
    }

    protected function activityLogName(): string
    {
        return 'calendar-actions';
    }
}

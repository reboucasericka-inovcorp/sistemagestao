<?php

namespace App\Models\Calendar;

use App\Models\Concerns\HasActivityLog;
use App\Models\EntityModel;
use App\Models\Settings\CalendarActionModel;
use App\Models\Settings\CalendarTypeModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEventModel extends Model
{
    use HasActivityLog;

    protected $table = 'calendar_events';

    protected $fillable = [
        'date',
        'time',
        'duration',
        'user_id',
        'entity_id',
        'type_id',
        'action_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'entity_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CalendarTypeModel::class, 'type_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(CalendarActionModel::class, 'action_id');
    }

    protected function activityLogName(): string
    {
        return 'calendar-events';
    }
}

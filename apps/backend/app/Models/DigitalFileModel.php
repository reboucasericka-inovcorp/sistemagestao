<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DigitalFileModel extends Model
{
    protected $table = 'digital_files';

    protected $fillable = [
        'name',
        'file_path',
        'mime_type',
        'size',
        'category',
        'description',
        'uploaded_by',
        'entity_id',
        'fileable_id',
        'fileable_type',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(EntityModel::class, 'entity_id');
    }

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}

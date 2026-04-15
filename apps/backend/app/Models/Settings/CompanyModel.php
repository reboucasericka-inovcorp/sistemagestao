<?php

namespace App\Models\Settings;

use App\Models\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Model;

class CompanyModel extends Model
{
    use HasActivityLog;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'tax_number',
        'address',
        'postal_code',
        'city',
        'country_id',
        'phone',
        'mobile',
        'email',
        'website',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'country_id' => 'integer',
        'is_active' => 'boolean',
    ];

    protected function activityLogName(): string
    {
        return 'company';
    }
}

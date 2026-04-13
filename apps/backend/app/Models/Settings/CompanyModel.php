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
        'logo_path',
        'address',
        'postal_code',
        'city',
        'tax_number',
    ];

    protected function activityLogName(): string
    {
        return 'company';
    }
}

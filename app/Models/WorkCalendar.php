<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Work calendar copied from Infor (FSC). See App\Support\InforCalendar for the day codes.
 */
class WorkCalendar extends Model
{
    protected $fillable = [
        'facility',
        'work_center',
        'year',
        'days',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'work_center' => 'integer',
            'year' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function isLeapYear(): bool
    {
        return CarbonImmutable::create($this->year)->isLeapYear();
    }
}

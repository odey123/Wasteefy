<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'report_type_id',
        'email',
        'phone_number',
        'address',
        'city',
        'state',
        'latitude',
        'longitude',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => ReportStatus::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            $report->reference ??= (string) Str::ulid();
            $report->status ??= ReportStatus::Submitted;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ReportType::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReportPhoto::class);
    }
}

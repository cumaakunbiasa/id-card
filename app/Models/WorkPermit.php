<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkPermit extends Model
{
    protected $fillable = [
        'group_id',
        'person_type',
        'foreman_name',
        'zone_key',
        'zone_color',
        'photo_path',
        'work_starts_at',
        'work_ends_at',
        'full_name',
        'company_name',
        'job_title',
        'work_location',
        'work_description',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'work_starts_at' => 'datetime',
            'work_ends_at' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'printed_at' => 'datetime',
            'decision_email_queued_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PermitApproval::class);
    }
}

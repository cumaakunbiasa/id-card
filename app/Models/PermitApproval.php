<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermitApproval extends Model
{
    protected $fillable = [
        'admin_user_id',
        'admin_role',
        'status',
        'notes',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function workPermit(): BelongsTo
    {
        return $this->belongsTo(WorkPermit::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'admin_user_id'
        );
    }
}

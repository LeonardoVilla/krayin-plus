<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Lead\Models\LeadProxy;
use Webkul\User\Models\UserProxy;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'lead_id',
        'user_id',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }
}

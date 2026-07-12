<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\UserProxy;

class ProjectTask extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'status',
        'due_date',
        'user_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Contact\Models\PersonProxy;
use Webkul\User\Models\UserProxy;

class SupportTicket extends Model
{
    protected $fillable = [
        'subject',
        'description',
        'status',
        'priority',
        'person_id',
        'user_id',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }
}

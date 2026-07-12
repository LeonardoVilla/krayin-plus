<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuditLogger
{
    protected static array $excludedModels = [
        \Illuminate\Notifications\DatabaseNotification::class,
        \App\Models\AuditLog::class,
    ];

    protected static array $labelFields = [
        'name', 'title', 'subject', 'label', 'quote_number', 'code', 'email', 'first_name',
    ];

    public static function handle(string $action, $model): void
    {
        try {
            if (in_array(get_class($model), static::$excludedModels, true)) {
                return;
            }

            $changes = [];

            if ($action === 'update') {
                foreach ($model->getChanges() as $field => $newValue) {
                    if (in_array($field, ['updated_at', 'remember_token'], true)) {
                        continue;
                    }
                    $oldValue = $model->getOriginal($field);
                    if ($oldValue === $newValue) {
                        continue;
                    }
                    $changes[$field] = [
                        'old' => is_scalar($oldValue) ? mb_substr((string) $oldValue, 0, 500) : $oldValue,
                        'new' => is_scalar($newValue) ? mb_substr((string) $newValue, 0, 500) : $newValue,
                    ];
                }

                if (empty($changes)) {
                    return;
                }
            }

            $user = Auth::guard('user')->user();

            DB::table('audit_logs')->insert([
                'model_type' => get_class($model),
                'model_id' => (string) $model->getKey(),
                'model_label' => static::label($model),
                'action' => $action,
                'user_id' => $user->id ?? null,
                'user_name' => $user->name ?? ($user->email ?? 'system'),
                'field_changes' => !empty($changes) ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => request()?->ip() ?? '',
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected static function label($model): string
    {
        foreach (static::$labelFields as $field) {
            $value = $model->{$field} ?? null;
            if (!empty($value) && is_scalar($value)) {
                return mb_substr((string) $value, 0, 255);
            }
        }

        return (string) $model->getKey();
    }
}

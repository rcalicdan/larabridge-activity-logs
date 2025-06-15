<?php

namespace Rcalicdan\LarabridgeActivityLogs\Logger;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Config\Audit as AuditConfig;
use Rcalicdan\LarabridgeActivityLogs\Models\ActivityLog;

class AuditLogger
{
    protected static ?AuditConfig $config = null;

    protected static function getConfig(): AuditConfig
    {
        if (static::$config === null) {
            static::$config = config('Audit');
        }

        return static::$config;
    }

    public static function created(Model $model): void
    {
        if (static::shouldAudit($model, 'created')) {
            static::log($model, 'created', [], $model->getAttributes());
        }
    }

    public static function updated(Model $model): void
    {
        if (!static::shouldAudit($model, 'updated')) {
            return;
        }

        $originalData = $model->getOriginal();
        $newData = $model->getAttributes();

        $changes = static::getChanges($originalData, $newData, $model);

        if (!empty($changes['old']) || !empty($changes['new'])) {
            static::log($model, 'updated', $changes['old'], $changes['new']);
        }
    }

    public static function deleted(Model $model): void
    {
        if (static::shouldAudit($model, 'deleted')) {
            static::log($model, 'deleted', $model->getOriginal(), []);
        }
    }

    public static function attached(Model $model, string $relation, $attachedIds, array $attributes = []): void
    {
        if (static::shouldAudit($model, 'attached')) {
            $attachedIds = is_array($attachedIds) ? $attachedIds : [$attachedIds];

            static::log($model, 'attached', [], [
                'relation' => $relation,
                'attached_ids' => $attachedIds,
                'pivot_attributes' => $attributes,
            ]);
        }
    }

    public static function detached(Model $model, string $relation, $detachedIds): void
    {
        if (static::shouldAudit($model, 'detached')) {
            $detachedIds = is_array($detachedIds) ? $detachedIds : [$detachedIds];

            static::log($model, 'detached', [
                'relation' => $relation,
                'detached_ids' => $detachedIds,
            ], []);
        }
    }

    public static function synced(Model $model, string $relation, array $changes): void
    {
        if (static::shouldAudit($model, 'synced')) {
            if (!empty($changes['attached']) || !empty($changes['detached']) || !empty($changes['updated'])) {
                static::log($model, 'synced', [], [
                    'relation' => $relation,
                    'changes' => $changes,
                ]);
            }
        }
    }

    protected static function log(Model $model, string $event, array $oldValues, array $newValues): void
    {
        $config = static::getConfig();
        $oldValues = static::filterAttributes($oldValues, $model);
        $newValues = static::filterAttributes($newValues, $model);

        $auditData = [
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => static::getCurrentUserId(),
        ];

        if ($config->storeIpAddress) {
            $auditData['ip_address'] = static::getClientIpAddress();
        }

        if ($config->storeUserAgent) {
            $auditData['user_agent'] = static::getUserAgent();
        }

        ActivityLog::create($auditData);


        // Clean up old audit logs if enabled
        if ($config->maxAuditLogs > 0) {
            static::cleanupOldAuditLogs($model, $config->maxAuditLogs);
        }
    }

    protected static function getChanges(array $original, array $current, Model $model): array
    {
        $oldValues = [];
        $newValues = [];

        foreach ($current as $key => $value) {
            if (array_key_exists($key, $original) && $original[$key] !== $value) {
                $oldValues[$key] = $original[$key];
                $newValues[$key] = $value;
            }
        }

        return ['old' => $oldValues, 'new' => $newValues];
    }

    protected static function filterAttributes(array $attributes, Model $model): array
    {
        $config = static::getConfig();
        $excludedAttributes = $config->excludedAttributes;

        // Add model-specific excluded attributes
        if (method_exists($model, 'getAuditExclude')) {
            $excludedAttributes = array_merge($excludedAttributes, $model->getAuditExclude());
        }

        return array_diff_key($attributes, array_flip($excludedAttributes));
    }

    protected static function shouldAudit(Model $model, string $event): bool
    {
        $config = static::getConfig();

        // Check if auditing is globally enabled
        if (!$config->enabled) {
            return false;
        }

        // Check if this event should be audited
        if (!in_array($event, $config->events)) {
            return false;
        }

        // Check if model should be audited
        if (method_exists($model, 'shouldAudit') && !$model->shouldAudit()) {
            return false;
        }

        return true;
    }

    protected static function cleanupOldAuditLogs(Model $model, int $maxLogs): void
    {
        $totalLogs = ActivityLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey())
            ->count();

        if ($totalLogs > $maxLogs) {
            $logsToDelete = $totalLogs - $maxLogs;

            ActivityLog::where('auditable_type', get_class($model))
                ->where('auditable_id', $model->getKey())
                ->orderBy('created_at', 'asc')
                ->limit($logsToDelete)
                ->delete();
        }
    }

    protected static function getCurrentUserId(): ?int
    {
        try {
            return auth()->user()?->id;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected static function getClientIpAddress(): ?string
    {
        try {
            return service('request')->getIPAddress();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected static function getUserAgent(): ?string
    {
        try {
            return service('request')->getUserAgent()->getAgentString();
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function setConfig(AuditConfig $config): void
    {
        static::$config = $config;
    }
}

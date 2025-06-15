<?php

namespace Rcalicdan\LarabridgeActivityLogs\Logger;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Rcalicdan\LarabridgeActivityLogs\Models\ActivityLog;

trait Auditable
{
    /**
     * Attributes to exclude from auditing for this model
     */
    protected $auditExclude = [];

    /**
     * Disable auditing for this model instance
     */
    protected $auditEnabled = true;

    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            AuditLogger::created($model);
        });

        static::updated(function ($model) {
            AuditLogger::updated($model);
        });

        static::deleted(function ($model) {
            AuditLogger::deleted($model);
        });
    }

    /**
     * Get the attributes that should be excluded from auditing
     */
    public function getAuditExclude(): array
    {
        return $this->auditExclude;
    }

    /**
     * Set the attributes that should be excluded from auditing
     */
    public function setAuditExclude(array $attributes): void
    {
        $this->auditExclude = $attributes;
    }

    /**
     * Add attributes to exclude from auditing
     */
    public function addAuditExclude(array $attributes): void
    {
        $this->auditExclude = array_merge($this->auditExclude, $attributes);
    }

    /**
     * Override attach method to log many-to-many attachments
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        $relation = $this->getRelationFromBacktrace();
        
        if ($relation) {
            $result = parent::attach($id, $attributes, $touch);
            AuditLogger::attached($this, $relation, $id, $attributes);
            return $result;
        }
        
        return parent::attach($id, $attributes, $touch);
    }

    /**
     * Override detach method to log many-to-many detachments
     */
    public function detach($ids = null, $touch = true)
    {
        $relation = $this->getRelationFromBacktrace();
        
        if ($relation) {
            $result = parent::detach($ids, $touch);
            AuditLogger::detached($this, $relation, $ids);
            return $result;
        }
        
        return parent::detach($ids, $touch);
    }

    /**
     * Override sync method to log many-to-many sync operations
     */
    public function sync($ids, $detaching = true)
    {
        $relation = $this->getRelationFromBacktrace();
        
        if ($relation) {
            $changes = parent::sync($ids, $detaching);
            AuditLogger::synced($this, $relation, $changes);
            return $changes;
        }
        
        return parent::sync($ids, $detaching);
    }

    /**
     * Get the relation name from backtrace
     */
    protected function getRelationFromBacktrace(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        
        foreach ($trace as $frame) {
            if (isset($frame['object']) && $frame['object'] instanceof BelongsToMany) {
                return $frame['function'];
            }
        }
        
        return null;
    }

    /**
     * Disable auditing for this model instance
     */
    public function withoutAuditing(\Closure $callback)
    {
        $originalAuditEnabled = $this->auditEnabled;
        $this->auditEnabled = false;
        
        try {
            return $callback();
        } finally {
            $this->auditEnabled = $originalAuditEnabled;
        }
    }

    /**
     * Check if model should be audited
     */
    public function shouldAudit(): bool
    {
        return $this->auditEnabled;
    }

    /**
     * Disable auditing for this model
     */
    public function disableAuditing(): void
    {
        $this->auditEnabled = false;
    }

    /**
     * Enable auditing for this model
     */
    public function enableAuditing(): void
    {
        $this->auditEnabled = true;
    }

    /**
     * Get all audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(ActivityLog::class, 'auditable');
    }
}
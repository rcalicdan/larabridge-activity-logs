<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Audit extends BaseConfig
{
    /**
     * Enable or disable auditing globally
     */
    public bool $enabled = true;

    /**
     * Attributes to exclude from auditing by default
     */
    public array $excludedAttributes = [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'password',
        'password_confirmation',
    ];

    /**
     * Events to audit
     */
    public array $events = [
        'created',
        'updated',
        'deleted',
        'attached',
        'detached',
        'synced',
    ];

    /**
     * Maximum number of audit logs to keep per model (0 = unlimited)
     */
    public int $maxAuditLogs = 0;

    /**
     * Automatically clean up old audit logs
     */
    public bool $autoCleanup = false;

    /**
     * Number of days to keep audit logs (used with autoCleanup)
     */
    public int $keepAuditLogsForDays = 365;

    /**
     * Store IP address in audit logs
     */
    public bool $storeIpAddress = true;

    /**
     * Store user agent in audit logs
     */
    public bool $storeUserAgent = true;
}
<?php

namespace Rcalicdan\LarabridgeActivityLogs\Commands\Handlers\ActivityLogHandler;

use Rcalicdan\Ci4Larabridge\Commands\Handlers\LaravelSetup\SetupHandler;

class ConfigHandler extends SetupHandler
{
    /**
     * Publish all required configuration files
     */
    public function publishConfig(): void
    {
        $this->publishConfigAudit();
    }

    private function publishConfigAudit(): void
    {
        $file = 'Config/Audit.php';
        $replaces = [
            'namespace Rcalicdan\Ci4Larabridge\Config' => 'namespace Config',
        ];

        $this->copyAndReplace($file, $replaces);
    }
}

<?php

namespace Rcalicdan\LarabridgeActivityLogs\Commands;

use CodeIgniter\CLI\BaseCommand;
use Rcalicdan\Ci4Larabridge\Commands\Handlers\LaravelSetup\MigrationHandler;
use Rcalicdan\LarabridgeActivityLogs\Commands\Handlers\ActivityLogHandler\ConfigHandler;

/**
 * Command to perform the initial setup for the CodeIgniter 4 Laravel Module.
 *
 * This command orchestrates the setup process by initializing handlers and
 * executing steps to configure the Laravel module within a CodeIgniter 4
 * application. It publishes configuration files, sets up helpers, copies migration
 * files, configures system events and filters, and prepares authentication components.
 */
class ActivityLogSetup extends BaseCommand
{
    /**
     * The group this command belongs to.
     *
     * @var string
     */
    protected $group = 'Larabridge Plugin Setup';

    /**
     * The name of the command.
     *
     * @var string
     */
    protected $name = 'activty-log:setup';

    /**
     * A brief description of the command's purpose.
     *
     * @var string
     */
    protected $description = 'Initial setup for Activity Logs Module.';

    /**
     * The command's usage instructions.
     *
     * @var string
     */
    protected $usage = 'activty-log:setup';

    /**
     * Available arguments for the command.
     *
     * @var array<string, string>
     */
    protected $arguments = [];

    /**
     * Available options for the command.
     *
     * @var array<string, string>
     */
    protected $options = [
        '-f' => 'Force overwrite ALL existing files in destination.',
    ];

    /**
     * The path to the source directory of the Ci4Larabridge module.
     *
     * @var string
     */
    protected $sourcePath;

    /**
     * The path to the application directory.
     *
     * @var string
     */
    protected $distPath = APPPATH;

    /**
     * Executes the setup process for the Laravel module.
     *
     * Initializes handlers for configuration, helpers, migrations, system components,
     * and authentication, then executes their respective setup steps. Supports a force
     * overwrite option for existing files.
     *
     * @param  array  $params  Command parameters, including options.
     */
    public function run(array $params): void
    {
        $this->sourcePath = __DIR__.'/../';

        $configHandler = new ConfigHandler($this->sourcePath, $this->distPath);
        $migrationHandler = new MigrationHandler($this->sourcePath, $this->distPath);

        $configHandler->publishConfig();
        $migrationHandler->copyMigrationFiles();
    }
}

<?php

namespace App\Console\Commands\Plugin;

use App\Services\Plugins\PluginInstallService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class InstallPluginCommand extends Command
{
    protected $signature = 'p:plugin:install {name}';

    protected $description = 'Installs a plugin';

    public function __construct(private Client $client)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        try {
            $packageName = $this->argument('name');

            /** @var PluginInstallService $installService */
            $installService = resolve(PluginInstallService::class);
            $plugin = $installService->installFromUrl("https://raw.githubusercontent.com/{$packageName}/main/install.json");

            $this->info("Plugin '{$plugin->name}' was installed successfully.");
        } catch (Exception $exception) {
            $this->error('Could not install plugin: ' . $exception->getMessage());
        }
    }
}

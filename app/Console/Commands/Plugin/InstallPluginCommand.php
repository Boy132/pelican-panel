<?php

namespace App\Console\Commands\Plugin;

use App\Services\Plugins\PluginInstallService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class InstallPluginCommand extends Command
{
    protected $signature = 'p:plugin:install {plugin}';

    protected $description = 'Install a plugin';

    public function __construct(private Client $client)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        try {
            $plugin = $this->argument('plugin');

            /** @var PluginInstallService $installService */
            $installService = resolve(PluginInstallService::class);
            $plugin = $installService->installFromUrl(filter_var($plugin, FILTER_VALIDATE_URL) ? $plugin : "https://raw.githubusercontent.com/{$plugin}/main/install.json");

            $this->info("Plugin '{$plugin->name}' was installed successfully.");
        } catch (Exception $exception) {
            $this->error('Could not install plugin: ' . $exception->getMessage());
        }
    }
}

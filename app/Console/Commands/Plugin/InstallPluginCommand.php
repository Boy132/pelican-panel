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
        /** @var PluginInstallService $installService */
        $installService = resolve(PluginInstallService::class);

        $packageName = $this->argument('name');

        try {
            $response = $this->client->request('GET', "https://raw.githubusercontent.com/{$packageName}/main/install.json");
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                $plugin = $installService->install($data);

                $this->info("Plugin '{$plugin->name}' was installed successfully.");
            } else {
                $this->error("Could not install plugin: {$response->getStatusCode()} {$response->getReasonPhrase()}");
            }
        } catch (Exception $exception) {
            $this->error('Could not install plugin: ' . $exception->getMessage());
        }
    }
}

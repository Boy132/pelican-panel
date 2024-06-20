<?php

namespace App\Console\Commands\Plugin;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class InstallPluginsCommand extends Command
{
    protected $signature = 'p:plugin:install';

    protected $description = 'Installs all plugins (that are not disabled) via composer require';

    public function handle(): void
    {
        $pluginPackages = Plugin::query()->whereNot('status', PluginStatus::Disabled)->select('package')->pluck('package')->toArray();

        if (count($pluginPackages) < 1) {
            $this->line('No plugins installed');

            return;
        }

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->requirePackages($pluginPackages, false, $this->output);
    }
}

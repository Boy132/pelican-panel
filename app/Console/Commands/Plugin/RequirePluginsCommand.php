<?php

namespace App\Console\Commands\Plugin;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class RequirePluginsCommand extends Command
{
    protected $signature = 'p:plugin:require';

    protected $description = 'Install all plugin packages via composer require';

    public function handle(): void
    {
        $pluginPackages = Plugin::query()->whereNot('status', PluginStatus::Disabled)->select('package')->pluck('package')->toArray();

        if (count($pluginPackages) < 1) {
            $this->warn('No plugins installed');

            return;
        }

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->setWorkingPath(base_path());
        $composer->requirePackages($pluginPackages, false, $this->output);
    }
}

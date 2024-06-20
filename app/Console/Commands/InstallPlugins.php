<?php

namespace App\Console\Commands;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class InstallPlugins extends Command
{
    protected $signature = 'p:install-plugins';

    protected $description = 'Installs all plugins (that are not disabled) via composer require';

    public function handle()
    {
        $pluginPackages = Plugin::query()->whereNot('status', PluginStatus::Disabled)->select('package')->pluck('package')->toArray();

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->requirePackages($pluginPackages, false, $this->output);
    }
}

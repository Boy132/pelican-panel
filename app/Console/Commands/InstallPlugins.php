<?php

namespace App\Console\Commands;

use App\Models\Plugin;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class InstallPlugins extends Command
{
    protected $signature = 'p:install-plugins';

    protected $description = 'Installs all plugins via composer require';

    public function handle()
    {
        $pluginPackages = Plugin::query()->where('enabled', true)->select('package')->pluck('package')->toArray();

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->requirePackages($pluginPackages, false, $this->output);
    }
}

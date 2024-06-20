<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use Illuminate\Support\Composer;

class PluginInstallService
{
    /**
     * Install a new plugin.
     */
    public function install(array $data): Plugin
    {
        $plugin = Plugin::create($data);
        $plugin->saveOrFail();

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->requirePackages([$plugin->package]);

        return $plugin;
    }

    /**
     * Uninstall a plugin.
     */
    public function uninstall(Plugin|string $plugin): void
    {
        if (!$plugin instanceof Plugin) {
            $plugin = Plugin::query()->findOrFail($plugin);
        }

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->removePackages([$plugin->package]);

        $plugin->delete();
    }
}

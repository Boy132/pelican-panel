<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use Illuminate\Support\Composer;

class PluginInstallService
{
    /**
     * Install a new plugin.
     * This will also require the composer package of the plugin.
     */
    public function install(array $data): Plugin
    {
        $plugin = Plugin::create($data);
        $plugin->saveOrFail();

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->setWorkingPath(base_path());
        $composer->requirePackages([$plugin->package]);

        return $plugin;
    }

    /**
     * Uninstall a plugin.
     * This will also remove the composer package of the plugin.
     */
    public function uninstall(Plugin|string $plugin): void
    {
        if (!$plugin instanceof Plugin) {
            $plugin = Plugin::query()->findOrFail($plugin);
        }

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->setWorkingPath(base_path());
        $composer->removePackages([$plugin->package]);

        $plugin->delete();
    }

    /**
     * Update a plugin.
     * Does not check if an actual update is available but just downloads the latest version.
     */
    public function update(Plugin|string $plugin): Plugin
    {
        if (!$plugin instanceof Plugin) {
            $plugin = Plugin::query()->findOrFail($plugin);
        }

        $updateData = $plugin->getUpdateData();
        $plugin->fill($updateData)->save();

        /** @var Composer $composer */
        $composer = app(Composer::class);
        $composer->setWorkingPath(base_path());
        $composer->requirePackages([$plugin->package]);

        return $plugin;
    }
}

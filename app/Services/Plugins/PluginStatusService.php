<?php

namespace App\Services\Plugins;

use App\Enums\PluginStatus;
use App\Models\Plugin;

class PluginStatusService
{
    /**
     * Enable a plugin.
     */
    public function enable(Plugin|string $plugin): void
    {
        if (!$plugin instanceof Plugin) {
            $plugin = Plugin::query()->findOrFail($plugin);
        }

        $plugin->status = PluginStatus::Enabled;
        $plugin->status_message = null;
        $plugin->saveOrFail();
    }

    /**
     * Disable a plugin.
     */
    public function disable(Plugin|string $plugin): void
    {
        if (!$plugin instanceof Plugin) {
            $plugin = Plugin::query()->findOrFail($plugin);
        }

        $plugin->status = PluginStatus::Disabled;
        $plugin->status_message = null;
        $plugin->saveOrFail();
    }
}

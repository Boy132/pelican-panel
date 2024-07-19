<?php

namespace App\Traits\Helpers;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use Exception;
use Filament\Panel;
use Illuminate\Support\Facades\Schema;

trait PluginLoaderTrait
{
    protected function loadPanelPlugins(Panel $panel): void
    {
        // Don't load any plugins during tests
        if (app()->runningUnitTests()) {
            return;
        }

        // Make sure the plugins table exist. Otherwise, migrations would fail on first install.
        if (!Schema::hasTable('plugins')) {
            return;
        }

        $plugins = Plugin::query()->whereNot('status', PluginStatus::Disabled)->get();
        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            if (!$plugin->shouldLoad($panel->getId())) {
                continue;
            }

            try {
                $pluginClass = $plugin->class;

                if (!class_exists($pluginClass)) {
                    throw new Exception('Class "' . $pluginClass . '" not found');
                }

                $panel->plugin($pluginClass::make());

                $plugin->status = PluginStatus::Enabled;
            } catch (Exception $exception) {
                $plugin->status = PluginStatus::Errored;

                report($exception);
            }

            $plugin->save();
        }
    }
}

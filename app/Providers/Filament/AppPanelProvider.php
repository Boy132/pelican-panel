<?php

namespace App\Providers\Filament;

use App\Services\Helpers\PluginService;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Panel;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = parent::panel($panel)
            ->id('app')
            ->default()
            ->breadcrumbs(false)
            ->navigation(false)
            ->userMenuItems([
                MenuItem::make()
                    ->label(trans('profile.admin'))
                    ->url('/admin')
                    ->icon('tabler-arrow-forward')
                    ->sort(5)
                    ->visible(fn () => auth()->user()->canAccessPanel(Filament::getPanel('admin'))),
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources');

        app(PluginService::class)->loadPanelPlugins(app(), $panel); // @phpstan-ignore-line

        return $panel;
    }
}

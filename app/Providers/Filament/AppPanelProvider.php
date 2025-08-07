<?php

namespace App\Providers\Filament;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Panel;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->id('app')
            ->default();

        return parent::panel($panel)
            ->breadcrumbs(false)
            ->navigation(false)
            ->userMenuItems([
                Action::make('to_admin')
                    ->label(trans('profile.admin'))
                    ->url(fn () => Filament::getPanel('admin')->getUrl())
                    ->icon('tabler-arrow-forward')
                    ->visible(fn () => auth()->user()->canAccessPanel(Filament::getPanel('admin'))),
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources');
    }
}

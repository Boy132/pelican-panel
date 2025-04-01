<?php

namespace App\Providers\Filament;

use App\Extensions\Avatar\AvatarProvider;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\EditProfile;
use App\Services\Helpers\PluginService;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->id('app')
            ->spa()
            ->databaseNotifications()
            ->breadcrumbs(false)
            ->brandName(config('app.name', 'Pelican'))
            ->brandLogo(config('app.logo'))
            ->brandLogoHeight('2rem')
            ->favicon(config('app.favicon', '/pelican.ico'))
            ->topNavigation(config('panel.filament.top-navigation', true))
            ->maxContentWidth(config('panel.filament.display-width', 'screen-2xl'))
            ->defaultAvatarProvider(fn () => get_class(AvatarProvider::getProvider(config('panel.filament.avatar-provider'))))
            ->navigation(false)
            ->profile(EditProfile::class, false)
            ->login(Login::class)
            ->passwordReset()
            ->userMenuItems([
                MenuItem::make()
                    ->label('Admin')
                    ->url('/admin')
                    ->icon('tabler-arrow-forward')
                    ->sort(5)
                    ->visible(fn (): bool => auth()->user()->canAccessPanel(Filament::getPanel('admin'))),
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        app(PluginService::class)->loadPanelPlugins(app(), $panel); // @phpstan-ignore-line

        return $panel;
    }
}

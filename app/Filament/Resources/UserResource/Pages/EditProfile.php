<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Users\TwoFactorSetupService;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

class EditProfile extends \Filament\Pages\Auth\EditProfile
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Tabs::make()->schema([
                            Tab::make('Account')
                                ->icon('tabler-user')
                                ->schema([
                                    TextInput::make('username')
                                        ->disabled()
                                        ->readOnly()
                                        ->maxLength(191)
                                        ->unique(ignoreRecord: true)
                                        ->autofocus(),

                                    TextInput::make('email')
                                        ->prefixIcon('tabler-mail')
                                        ->email()
                                        ->required()
                                        ->maxLength(191)
                                        ->unique(ignoreRecord: true),

                                    TextInput::make('password')
                                        ->password()
                                        ->prefixIcon('tabler-password')
                                        ->revealable(filament()->arePasswordsRevealable())
                                        ->rule(Password::default())
                                        ->autocomplete('new-password')
                                        ->dehydrated(fn ($state): bool => filled($state))
                                        ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                                        ->live(debounce: 500)
                                        ->same('passwordConfirmation'),

                                    TextInput::make('passwordConfirmation')
                                        ->password()
                                        ->prefixIcon('tabler-password-fingerprint')
                                        ->revealable(filament()->arePasswordsRevealable())
                                        ->required()
                                        ->visible(fn (Get $get): bool => filled($get('password')))
                                        ->dehydrated(false),

                                    Select::make('language')
                                        ->required()
                                        ->prefixIcon('tabler-flag')
                                        ->live()
                                        ->default('en')
                                        ->helperText(fn (User $user, $state) => new HtmlString($user->isLanguageTranslated($state) ? '' : "
                                                Your language ($state) has not been translated yet!
                                                But never fear, you can help fix that by
                                                <a style='color: rgb(56, 189, 248)' href='https://crowdin.com/project/pelican-dev'>contributing directly here</a>.
                                            ")
                                        )
                                        ->options(fn (User $user) => $user->getAvailableLanguages()),
                                ]),

                            Tab::make('2FA')
                                ->icon('tabler-shield-lock')
                                ->schema(function () {

                                    if ($this->getUser()->use_totp) {
                                        return [
                                            Placeholder::make('2FA already enabled!'),
                                        ];
                                    }
                                    $setupService = app(TwoFactorSetupService::class);

                                    ['image_url_data' => $url] = $setupService->handle($this->getUser());

                                    $options = new QROptions([
                                        'svgLogo' => public_path('pelican.svg'),
                                        'addLogoSpace' => true,
                                        'logoSpaceWidth' => 13,
                                        'logoSpaceHeight' => 13,
                                    ]);

                                    // https://github.com/chillerlan/php-qrcode/blob/main/examples/svgWithLogo.php

                                    // SVG logo options (see extended class)
                                    $options->svgLogo = public_path('pelican.svg'); // logo from: https://github.com/simple-icons/simple-icons
                                    $options->svgLogoScale = 0.05;
                                    // $options->svgLogoCssClass     = 'dark';

                                    // QROptions
                                    $options->version = Version::AUTO;
                                    // $options->outputInterface     = QRSvgWithLogo::class;
                                    $options->outputBase64 = false;
                                    $options->eccLevel = EccLevel::H; // ECC level H is necessary when using logos
                                    $options->addQuietzone = true;
                                    // $options->drawLightModules    = true;
                                    $options->connectPaths = true;
                                    $options->drawCircularModules = true;
                                    // $options->circleRadius        = 0.45;

                                    $options->svgDefs = '<linearGradient id="gradient" x1="100%" y2="100%">
                                            <stop stop-color="#7dd4fc" offset="0"/>
                                            <stop stop-color="#38bdf8" offset="0.5"/>
                                            <stop stop-color="#0369a1" offset="1"/>
                                        </linearGradient>
                                        <style><![CDATA[
                                            .dark{fill: url(#gradient);}
                                            .light{fill: #000;}
                                        ]]></style>';

                                    $image = (new QRCode($options))->render($url);

                                    return [
                                        Placeholder::make('qr')
                                            ->label('Scan QR Code')
                                            ->content(fn () => new HtmlString("
                                                <div style='width: 300px'>$image</div>
                                            "))
                                            ->default('asdfasdf'),
                                    ];
                                }),

                            Tab::make('API Keys')
                                ->icon('tabler-key')
                                ->schema([
                                    Placeholder::make('Coming soon!'),
                                    TagsInput::make('allowed_ips')
                                        ->placeholder('Example: 127.0.0.1 or 192.168.1.1')
                                        ->label('Whitelisted IPv4 Addresses')
                                        ->helperText('Press enter to add a new IP address or leave blank to allow any IP address')
                                        ->columnSpanFull()
                                        ->hidden()
                                        ->default(null),
                                ]),

                            Tab::make('SSH Keys')
                                ->icon('tabler-lock-code')
                                ->schema([
                                    Placeholder::make('Coming soon!'),
                                ]),

                            Tab::make('Activity')
                                ->icon('tabler-history')
                                ->schema([
                                    Repeater::make('activity')
                                        ->deletable(false)
                                        ->addable(false)
                                        ->relationship()

                                        ->schema([
                                            Placeholder::make('activity!')->label('')->content(fn (ActivityLog $log) => new HtmlString($log->htmlable())),
                                        ]),
                                ]),
                        ]),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(!static::isSimple()),
            ),
        ];
    }
}

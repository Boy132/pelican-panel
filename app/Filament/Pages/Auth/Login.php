<?php

namespace App\Filament\Pages\Auth;

use App\Events\Auth\ProvidedAuthenticationToken;
use App\Extensions\Captcha\CaptchaService;
use App\Extensions\OAuth\OAuthService;
use App\Facades\Activity;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Sleep;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class Login extends BaseLogin
{
    private Google2FA $google2FA;

    public bool $verifyTwoFactor = false;

    protected OAuthService $oauthService;

    protected CaptchaService $captchaService;

    public function boot(Google2FA $google2FA, OAuthService $oauthService, CaptchaService $captchaService): void
    {
        $this->google2FA = $google2FA;
        $this->oauthService = $oauthService;
        $this->captchaService = $captchaService;
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        Filament::auth()->once($this->getCredentialsFromFormData($data));

        /** @var ?User $user */
        $user = Filament::auth()->user();

        // Make sure that rate limits apply
        if (!$user) {
            return parent::authenticate();
        }

        // 2FA disabled
        if (!$user->use_totp) {
            return parent::authenticate();
        }

        $token = $data['2fa'] ?? null;

        // 2FA not shown yet
        if ($token === null) {
            $this->verifyTwoFactor = true;

            Activity::event('auth:checkpoint')
                ->withRequestMetadata()
                ->subject($user)
                ->log();

            return null;
        }

        $isValidToken = false;
        if (strlen($token) === $this->google2FA->getOneTimePasswordLength()) {
            $isValidToken = $this->google2FA->verifyKey(
                $user->totp_secret,
                $token,
                Config::integer('panel.auth.2fa.window'),
            );

            if ($isValidToken) {
                event(new ProvidedAuthenticationToken($user));
            }
        } else {
            foreach ($user->recoveryTokens as $recoveryToken) {
                if (password_verify($token, $recoveryToken->token)) {
                    $isValidToken = true;
                    $recoveryToken->delete();

                    event(new ProvidedAuthenticationToken($user, true));

                    break;
                }
            }
        }

        if (!$isValidToken) {
            // Buffer to prevent bruteforce
            Sleep::sleep(1);

            Notification::make()
                ->title(trans('auth.failed-two-factor'))
                ->body(trans('auth.failed'))
                ->color('danger')
                ->icon('tabler-auth-2fa')
                ->danger()
                ->send();

            return null;
        }

        return parent::authenticate();
    }

    protected function getForms(): array
    {
        $schema = [
            $this->getLoginFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getRememberFormComponent(),
            $this->getOAuthFormComponent(),
            $this->getTwoFactorAuthenticationComponent(),
        ];

        if ($captchaComponent = $this->getCaptchaComponent()) {
            $schema = array_merge($schema, [$captchaComponent]);
        }

        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema($schema)
                    ->statePath('data'),
            ),
        ];
    }

    private function getTwoFactorAuthenticationComponent(): Component
    {
        return TextInput::make('2fa')
            ->label(trans('auth.two-factor-code'))
            ->hintIcon('tabler-question-mark')
            ->hintIconTooltip(trans('auth.two-factor-hint'))
            ->visible(fn () => $this->verifyTwoFactor)
            ->required()
            ->live();
    }

    private function getCaptchaComponent(): ?Component
    {
        return $this->captchaService->getActiveSchema()?->getFormComponent();
    }

    protected function throwFailureValidationException(): never
    {
        $this->dispatch('reset-captcha');

        throw ValidationException::withMessages([
            'data.login' => trans('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Login')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getOAuthFormComponent(): Component
    {
        $actions = [];

        $oauthSchemas = $this->oauthService->getEnabled();

        foreach ($oauthSchemas as $schema) {

            $id = $schema->getId();

            $actions[] = Action::make("oauth_$id")
                ->label($schema->getName())
                ->icon($schema->getIcon())
                ->color(Color::hex($schema->getHexColor()))
                ->url(route('auth.oauth.redirect', ['driver' => $id], false));
        }

        return Actions::make($actions);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $loginType = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $loginType => mb_strtolower($data['login']),
            'password' => $data['password'],
        ];
    }
}

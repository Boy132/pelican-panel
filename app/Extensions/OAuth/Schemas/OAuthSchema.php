<?php

namespace App\Extensions\OAuth\Schemas;

use App\Extensions\OAuth\OAuthSchemaInterface;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Illuminate\Support\Str;

abstract class OAuthSchema implements OAuthSchemaInterface
{
    abstract public function getId(): string;

    public function getSocialiteProvider(): ?string
    {
        return null;
    }

    public function getServiceConfig(): array
    {
        $id = Str::upper($this->getId());

        return [
            'client_id' => env("OAUTH_{$id}_CLIENT_ID"),
            'client_secret' => env("OAUTH_{$id}_CLIENT_SECRET"),
        ];
    }

    /**
     * @return Component[]
     */
    public function getSettingsForm(): array
    {
        $id = Str::upper($this->getId());

        return [
            TextInput::make("OAUTH_{$id}_CLIENT_ID")
                ->label('Client ID')
                ->placeholder('Client ID')
                ->columnSpan(2)
                ->required()
                ->password()
                ->revealable()
                ->autocomplete(false)
                ->default(env("OAUTH_{$id}_CLIENT_ID")),
            TextInput::make("OAUTH_{$id}_CLIENT_SECRET")
                ->label('Client Secret')
                ->placeholder('Client Secret')
                ->columnSpan(2)
                ->required()
                ->password()
                ->revealable()
                ->autocomplete(false)
                ->default(env("OAUTH_{$id}_CLIENT_SECRET")),
        ];
    }

    /**
     * @return Step[]
     */
    public function getSetupSteps(): array
    {
        return [
            Step::make('OAuth Config')
                ->columns(4)
                ->schema($this->getSettingsForm()),
        ];
    }

    public function getName(): string
    {
        return Str::title($this->getId());
    }

    public function getConfigKey(): string
    {
        $id = Str::upper($this->getId());

        return "OAUTH_{$id}_ENABLED";
    }

    public function getIcon(): ?string
    {
        return null;
    }

    public function getHexColor(): ?string
    {
        return null;
    }

    public function isEnabled(): bool
    {
        $id = Str::upper($this->getId());

        return env("OAUTH_{$id}_ENABLED", false);
    }
}

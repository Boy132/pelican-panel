<?php

namespace App\Filament\Resources\PluginResource\Pages;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Filament\Resources\PluginResource;
use App\Services\Plugins\PluginInstallService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables;

class ListPlugins extends ListRecords
{
    protected static string $resource = PluginResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->description(fn (Plugin $record): ?string => (strlen($record->description) > 60) ? substr($record->description, 0, 60).'...' : $record->description)
                    ->searchable(),
                Tables\Columns\TextColumn::make('author')
                    ->searchable(),
                Tables\Columns\TextColumn::make('panel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->icon(fn (PluginStatus $state) => $state->icon())
                    ->color(fn (PluginStatus $state) => $state->color())
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Plugin $record): string => 'https://github.com/' . $record->package),
                Tables\Actions\Action::make('enable')
                    ->color('success')
                    ->hidden(fn (Plugin $record) => !$record->isDisabled())
                    ->action(function (Plugin $record) {
                        $record->status = PluginStatus::Enabled;
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Plugin enabled')
                            ->send();
                    }),
                Tables\Actions\Action::make('disable')
                    ->color('danger')
                    ->hidden(fn (Plugin $record) => $record->isDisabled())
                    ->action(function (Plugin $record) {
                        $record->status = PluginStatus::Disabled;
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Plugin disabled')
                            ->send();
                    }),
                    Tables\Actions\Action::make('uninstall')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Plugin $record) => resolve(PluginInstallService::class)->uninstall($record)),
            ])
            ->emptyStateIcon('tabler-packages')
            ->emptyStateDescription('')
            ->emptyStateHeading('No Plugins')
            ->emptyStateActions([
                Tables\Actions\Action::make('install')
                    ->label('Install Plugin')
                    ->form(fn () => $this->installForm())
                    ->action(fn (array $data) => resolve(PluginInstallService::class)->install($data)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('install')
                ->label('Install Plugin')
                ->form(fn () => $this->installForm())
                ->action(fn (array $data) => resolve(PluginInstallService::class)->install($data)),
        ];
    }

    private function installForm(): array
    {
        // TOOD
        return [
            Forms\Components\TextInput::make('package')
                ->required(),
            Forms\Components\TextInput::make('class')
                ->required(),
            Forms\Components\Select::make('status')
                ->required()
                ->hidden()
                ->options(PluginStatus::class)
                ->default(PluginStatus::Enabled),
            Forms\Components\TextInput::make('name')
                ->required(),
            Forms\Components\TextInput::make('description')
                ->required(),
            Forms\Components\TextInput::make('author')
                ->required(),
            Forms\Components\Select::make('panel')
                ->required()
                ->options([
                    'admin' => 'Admin',
                    'app' => 'Client',
                    'both' => 'Admin & Client',
                ]),
            Forms\Components\Select::make('category')
                ->required()
                ->options([
                    'plugin' => 'Plugin',
                    'theme' => 'Theme',
                    'language' => 'Language',
                ]),
        ];
    }
}

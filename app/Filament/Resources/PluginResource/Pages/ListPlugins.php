<?php

namespace App\Filament\Resources\PluginResource\Pages;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Filament\Resources\PluginResource;
use App\Services\Plugins\PluginInstallService;
use App\Services\Plugins\PluginStatusService;
use Exception;
use Filament\Actions;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;

class ListPlugins extends ListRecords
{
    protected static string $resource = PluginResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->columns([
                TextColumn::make('name')
                    ->description(fn (Plugin $record): ?string => (strlen($record->description) > 60) ? substr($record->description, 0, 60).'...' : $record->description)
                    ->icon(fn (Plugin $record): string => $record->isCompatible() ? 'tabler-versions' : 'tabler-versions-off')
                    ->iconColor(fn (Plugin $record): string => $record->isCompatible() ? 'success' : 'danger')
                    ->tooltip(fn (Plugin $record): ?string => !$record->isCompatible() ? 'This Plugin is only compatible with Panel version ' . $record->panel_version . ' but you are using version!' : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('author')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('version')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->icon(fn (PluginStatus $state) => $state->icon())
                    ->iconColor(fn (PluginStatus $state) => $state->color())
                    ->tooltip(fn (Plugin $record): ?string => $record->status_message)
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->icon('tabler-eye-share')
                        ->color('primary')
                        ->url(fn (Plugin $record): string => 'https://github.com/' . $record->package, true),
                    Action::make('settings')
                        ->icon('tabler-settings')
                        ->color('primary')
                        ->visible(fn (Plugin $record) => !$record->isDisabled() && $record->hasSettings())
                        ->form(fn (Plugin $record) => $record->getSettingsForm())
                        ->action(fn (array $data, Plugin $record) => $record->saveSettings($data))
                        ->slideOver(),
                    Action::make('enable')
                        ->icon('tabler-check')
                        ->color('success')
                        ->hidden(fn (Plugin $record) => !$record->isDisabled())
                        ->action(function (Plugin $record) {
                            resolve(PluginStatusService::class)->enable($record);

                            Notification::make()
                                ->success()
                                ->title('Plugin enabled')
                                ->send();
                        }),
                    Action::make('disable')
                        ->icon('tabler-x')
                        ->color('danger')
                        ->hidden(fn (Plugin $record) => $record->isDisabled())
                        ->action(function (Plugin $record) {
                            resolve(PluginStatusService::class)->disable($record);

                            Notification::make()
                                ->success()
                                ->title('Plugin disabled')
                                ->send();
                        }),
                    Action::make('update')
                        ->icon('tabler-download')
                        ->color('primary')
                        ->visible(fn (Plugin $record) => $record->isUpdateAvailable())
                        ->action(fn (Plugin $record) => resolve(PluginInstallService::class)->update($record)),
                    Action::make('uninstall')
                        ->icon('tabler-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Plugin $record) => resolve(PluginInstallService::class)->uninstall($record)),
                ]),
            ])
            ->emptyStateIcon('tabler-packages')
            ->emptyStateDescription('')
            ->emptyStateHeading('No Plugins')
            ->emptyStateActions([
                Action::make('install')
                    ->label('Install Plugin')
                    ->form(fn () => $this->installForm())
                    ->action(fn (array $data) => $this->installAction($data)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('install')
                ->label('Install Plugin')
                ->form(fn () => $this->installForm())
                ->action(fn (array $data) => $this->installAction($data)),
        ];
    }

    private function installAction(array $data): void
    {
        $url = !empty($data['url']) ? $data['url'] : "https://raw.githubusercontent.com/{$data['name']}/main/install.json";

        try {
            /** @var PluginInstallService $installService */
            $installService = resolve(PluginInstallService::class);
            $installService->installFromUrl($url);
        } catch (Exception $exception) {
            Notification::make()
                ->title('Install Failed')
                ->danger()
                ->send();

            report($exception);
        }
    }

    private function installForm(): array
    {
        return [
            Tabs::make('Tabs')
                ->tabs([
                    Tab::make('Name')
                        ->icon('tabler-file-upload')
                        ->schema([
                            TextInput::make('name')
                                ->label('Plugin name')
                                ->hint('Enter the full name of the plugin repo, e.g. author/package-plugin'),
                        ]),
                    Tab::make('URL')
                        ->icon('tabler-world-upload')
                        ->schema([
                            TextInput::make('url')
                                ->label('Json URL')
                                ->hint('This URL should point to a single json file that contains the plugin install info.')
                                ->url(),
                        ]),
                ])
                ->contained(false),
        ];
    }
}

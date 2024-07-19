<?php

namespace App\Filament\Resources\PluginResource\Pages;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Filament\Resources\PluginResource;
use App\Services\Plugins\PluginInstallService;
use Exception;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
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
                    ->icon(fn (Plugin $record): string => $record->isCompatible() ? 'tabler-versions' : 'tabler-versions-off')
                    ->iconColor(fn (Plugin $record): string => $record->isCompatible() ? 'success' : 'danger')
                    ->tooltip(fn (Plugin $record): ?string => !$record->isCompatible() ? 'This Plugin is only compatible with Panel version ' . $record->panel_version . ' but you are using version!' : null)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('author')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->icon(fn (PluginStatus $state) => $state->icon())
                    ->iconColor(fn (PluginStatus $state) => $state->color())
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->icon('tabler-eye-share')
                        ->url(fn (Plugin $record): string => 'https://github.com/' . $record->package, true),
                    Tables\Actions\Action::make('enable')
                        ->icon('tabler-check')
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
                        ->icon('tabler-x')
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
                    Tables\Actions\Action::make('update')
                        ->icon('tabler-download')
                        ->color('primary')
                        ->hidden(fn (Plugin $record) => !$record->isUpdateAvailable())
                        ->action(fn (Plugin $record) => resolve(PluginInstallService::class)->update($record)),
                    Tables\Actions\Action::make('uninstall')
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
                Tables\Actions\Action::make('install')
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
                    Tabs\Tab::make('Name')
                        ->icon('tabler-file-upload')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Plugin name')
                                ->hint('Enter the full name of the plugin repo, e.g. author/package-plugin'),
                        ]),
                    Tabs\Tab::make('URL')
                        ->icon('tabler-world-upload')
                        ->schema([
                            Forms\Components\TextInput::make('url')
                                ->label('Json URL')
                                ->hint('This URL should point to a single json file that contains the plugin install info.')
                                ->url(),
                        ]),
                ])
                ->contained(false),
        ];
    }
}

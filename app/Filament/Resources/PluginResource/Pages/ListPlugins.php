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
        /** @var PluginInstallService $installService */
        $installService = resolve(PluginInstallService::class);

        try {
            // Install via json url
            if (!empty($data['url'])) {
                $url = $data['url'];
                $json = json_decode(file_get_contents($url), true, 512, JSON_THROW_ON_ERROR);
                $installService->install($json);

                return;
            }

            // Install via json file
            if (!empty($data['file'])) {
                $file = $data['file'];
                $fileData = json_decode($file->getContent(), true, 512, JSON_THROW_ON_ERROR);
                $installService->install($fileData);

                return;
            }

            // Install via manual input
            unset($data['url']);
            unset($data['file']);
            $installService->install($data);
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
                    Tabs\Tab::make('URL')
                        ->icon('tabler-world-upload')
                        ->schema([
                            Forms\Components\TextInput::make('url')
                                ->label('Json URL')
                                ->hint('This URL should point to a single json file that contains the plugin info.')
                                ->url(),
                        ]),
                    Tabs\Tab::make('File')
                        ->icon('tabler-file-upload')
                        ->schema([
                            Forms\Components\FileUpload::make('file')
                                ->label('Json File')
                                ->hint('This should be a single json file that contains the plugin info.')
                                ->acceptedFileTypes(['application/json'])
                                ->storeFiles(false),
                        ]),
                    Tabs\Tab::make('Manual')
                        ->icon('tabler-clipboard-list')
                        ->schema([
                            Forms\Components\TextInput::make('package'),
                            Forms\Components\TextInput::make('class'),
                            Forms\Components\Select::make('status')
                                ->hidden()
                                ->options(PluginStatus::class)
                                ->default(PluginStatus::Enabled),
                            Forms\Components\TextInput::make('name'),
                            Forms\Components\TextInput::make('description'),
                            Forms\Components\TextInput::make('author'),
                            Forms\Components\Select::make('panel')
                                ->options([
                                    'admin' => 'Admin',
                                    'app' => 'Client',
                                    'both' => 'Admin & Client',
                                ]),
                            Forms\Components\Select::make('category')
                                ->options([
                                    'plugin' => 'Plugin',
                                    'theme' => 'Theme',
                                    'language' => 'Language',
                                ]),
                        ]),
                ])
                ->contained(false),
        ];
    }
}

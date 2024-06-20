<?php

namespace App\Filament\Resources\PluginResource\Pages;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Filament\Resources\PluginResource;
use Filament\Actions;
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
                    ->description(fn (Plugin $record): ?string => (strlen($record->description) > 80) ? substr($record->description, 0, 80).'...' : $record->description)
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
            ])
            ->emptyStateIcon('tabler-packages')
            ->emptyStateDescription('')
            ->emptyStateHeading('No Plugins')
            ->emptyStateActions([
                Tables\Actions\Action::make('install') // TODO
                    ->label('Install Plugin'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('install') // TODO
                ->label('Install Plugin'),
        ];
    }
}

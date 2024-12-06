<?php

namespace App\Filament\Admin\Resources\MountResource\Pages;

use App\Filament\Admin\Resources\MountResource;
use App\Models\Mount;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListMounts extends ListRecords
{
    protected static string $resource = MountResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->columns([
                TextColumn::make('name')
                    ->description(fn (Mount $mount) => "$mount->source -> $mount->target")
                    ->sortable(),
                TextColumn::make('eggs.name')
                    ->icon('tabler-eggs')
                    ->label('Eggs')
                    ->badge()
                    ->placeholder('All eggs'),
                TextColumn::make('nodes.name')
                    ->icon('tabler-server-2')
                    ->label('Nodes')
                    ->badge()
                    ->placeholder('All nodes'),
                TextColumn::make('read_only')
                    ->label('Read only?')
                    ->badge()
                    ->icon(fn ($state) => $state ? 'tabler-writing-off' : 'tabler-writing')
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state ? 'Read only' : 'Writeable'),
                TextColumn::make('user_mountable')
                    ->label('User mountable?')
                    ->badge()
                    ->icon(fn ($state) => $state ? 'tabler-user-bolt' : 'tabler-user-cancel')
                    ->color(fn ($state) => $state ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn () => auth()->user()->can('delete mount')),
                ]),
            ])
            ->emptyStateIcon('tabler-layers-linked')
            ->emptyStateDescription('')
            ->emptyStateHeading('No Mounts')
            ->emptyStateActions([
                CreateAction::make('create')
                    ->label('Create Mount')
                    ->button(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Mount')
                ->hidden(fn () => Mount::count() <= 0),
        ];
    }
}

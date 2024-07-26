<?php

namespace App\Filament\Resources\EggRepoResource\Pages;

use App\Filament\Resources\EggRepoResource;
use App\Models\EggRepo;
use App\Services\Eggs\Sharing\EggImporterService;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListEggRepos extends ListRecords
{
    protected static string $resource = EggRepoResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('repo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tags')
                    ->badge()
                    ->state(fn (EggRepo $eggRepo) => array_unique(explode('/', $eggRepo->path))),
            ])
            ->filters([
                SelectFilter::make('repo')
                    ->options(fn () => EggRepo::all()->pluck('repo', 'repo')->unique())
                    ->multiple(),
            ])
            ->actions([
                Action::make('import')
                    ->icon('tabler-download')
                    ->label('Import')
                    ->color('primary')
                    ->action(fn (EggRepo $eggRepo) => resolve(EggImporterService::class)->fromUrl($eggRepo->download_url)),
            ]);
    }
}

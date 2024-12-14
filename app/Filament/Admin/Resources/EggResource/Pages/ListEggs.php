<?php

namespace App\Filament\Admin\Resources\EggResource\Pages;

use App\Filament\Admin\Resources\EggResource;
use App\Filament\Components\Actions\ImportEggAction;
use App\Models\Egg;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEggs extends ListRecords
{
    protected static string $resource = EggResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportEggAction::make()
                ->hidden(fn () => Egg::count() <= 0),
            CreateAction::make()
                ->label(__('filament-panels::resources/pages/create-record.title', ['label' => static::getResource()::getTitleCaseModelLabel()]))
                ->hidden(fn () => Egg::count() <= 0),
        ];
    }
}

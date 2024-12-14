<?php

namespace App\Filament\Admin\Resources\MountResource\Pages;

use App\Filament\Admin\Resources\MountResource;
use App\Models\Mount;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMounts extends ListRecords
{
    protected static string $resource = MountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-panels::resources/pages/create-record.title', ['label' => static::getResource()::getTitleCaseModelLabel()]))
                ->hidden(fn () => Mount::count() <= 0),
        ];
    }
}

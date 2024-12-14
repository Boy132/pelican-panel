<?php

namespace App\Filament\Admin\Resources\ServerResource\Pages;

use App\Filament\Admin\Resources\ServerResource;
use App\Models\Server;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServers extends ListRecords
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-panels::resources/pages/create-record.title', ['label' => static::getResource()::getTitleCaseModelLabel()]))
                ->hidden(fn () => Server::count() <= 0),
        ];
    }
}

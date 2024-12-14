<?php

namespace App\Filament\Admin\Resources\NodeResource\Pages;

use App\Filament\Admin\Resources\NodeResource;
use App\Models\Node;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNodes extends ListRecords
{
    protected static string $resource = NodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-panels::resources/pages/create-record.title', ['label' => static::getResource()::getTitleCaseModelLabel()]))
                ->hidden(fn () => Node::count() <= 0),
        ];
    }
}

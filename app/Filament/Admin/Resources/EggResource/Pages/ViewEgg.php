<?php

namespace App\Filament\Admin\Resources\EggResource\Pages;

use App\Filament\Admin\Resources\EggResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEgg extends ViewRecord
{
    protected static string $resource = EggResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

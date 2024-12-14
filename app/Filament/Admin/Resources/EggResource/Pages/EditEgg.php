<?php

namespace App\Filament\Admin\Resources\EggResource\Pages;

use App\Filament\Admin\Resources\EggResource;
use App\Filament\Components\Actions\ExportEggAction;
use App\Filament\Components\Actions\ImportEggAction;
use App\Models\Egg;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEgg extends EditRecord
{
    protected static string $resource = EggResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(fn (Egg $egg) => $egg->servers()->count() > 0 ? 'In Use' : __('filament-actions::delete.single.label'))
                ->disabled(fn (Egg $egg) => $egg->servers()->count() > 0),
            ExportEggAction::make(),
            ImportEggAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}

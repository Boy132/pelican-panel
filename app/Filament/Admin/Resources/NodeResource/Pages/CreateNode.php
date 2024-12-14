<?php

namespace App\Filament\Admin\Resources\NodeResource\Pages;

use App\Filament\Admin\Resources\NodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNode extends CreateRecord
{
    protected static string $resource = NodeResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrlParameters(): array
    {
        return [
            'tab' => '-configuration-file-tab',
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}

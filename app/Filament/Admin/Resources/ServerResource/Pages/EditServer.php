<?php

namespace App\Filament\Admin\Resources\ServerResource\Pages;

use App\Filament\Admin\Resources\ServerResource;
use App\Filament\Server\Pages\Console;
use App\Models\Server;
use App\Services\Servers\ServerDeletionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServer extends EditRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function (Server $server, ServerDeletionService $service) {
                    $service->handle($server);

                    return true;
                }),
            Action::make('console')
                ->label('Console')
                ->icon('tabler-terminal')
                ->url(fn (Server $server) => Console::getUrl(panel: 'server', tenant: $server)),
            $this->getSaveFormAction()->formId('form'),
        ];

    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!isset($data['description'])) {
            $data['description'] = '';
        }

        unset($data['docker'], $data['status']);

        return $data;
    }
}

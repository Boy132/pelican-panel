<?php

namespace App\Filament\Admin\Resources\WebhookResource\Pages;

use App\Filament\Admin\Resources\WebhookResource;
use App\Models\WebhookConfiguration;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebhookConfigurations extends ListRecords
{
    protected static string $resource = WebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-panels::resources/pages/create-record.title', ['label' => static::getResource()::getTitleCaseModelLabel()]))
                ->hidden(fn () => WebhookConfiguration::count() <= 0),
        ];
    }
}

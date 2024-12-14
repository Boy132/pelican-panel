<?php

namespace App\Filament\Admin\Resources\NodeResource\Pages;

use App\Filament\Admin\Resources\NodeResource;
use App\Models\Node;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNode extends ViewRecord
{
    protected static string $resource = NodeResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $node = Node::findOrFail($data['id']);

        $data['config'] = $node->getYamlConfiguration();

        if (!is_ip($node->fqdn)) {
            $validRecords = gethostbynamel($node->fqdn);
            if ($validRecords) {
                $data['dns'] = true;
                $data['ip'] = collect($validRecords)->first();
            } else {
                $data['dns'] = false;
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    // Used by chart widgets
    protected function getColumnSpan(): ?int
    {
        return null;
    }

    // Used by chart widgets
    protected function getColumnStart(): ?int
    {
        return null;
    }
}

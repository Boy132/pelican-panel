<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EggRepoResource\Pages;
use App\Models\EggRepo;
use Filament\Resources\Resource;

class EggRepoResource extends Resource
{
    protected static ?string $model = EggRepo::class;

    protected static ?string $navigationIcon = 'tabler-eggs';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEggRepos::route('/'),
        ];
    }
}

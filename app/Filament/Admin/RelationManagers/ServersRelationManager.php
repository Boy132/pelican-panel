<?php

namespace App\Filament\Admin\RelationManagers;

use App\Filament\Admin\Resources\EggResource\Pages\EditEgg;
use App\Filament\Admin\Resources\NodeResource\Pages\EditNode;
use App\Filament\Admin\Resources\ServerResource\Pages\EditServer;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Models\Egg;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class ServersRelationManager extends RelationManager
{
    protected static string $relationship = 'servers';

    protected static ?string $icon = 'tabler-brand-docker';

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No Servers')
            ->emptyStateDescription('No Servers are assigned to this ' . class_basename($this->getOwnerRecord()) . '.')
            ->columns([
                TextColumn::make('name')
                    ->icon('tabler-brand-docker')
                    ->url(fn (Server $server) => EditServer::getUrl(['record' => $server]))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.username')
                    ->hidden(fn () => $this->getOwnerRecord() instanceof User)
                    ->label('Owner')
                    ->icon('tabler-user')
                    ->url(fn (Server $server) => EditUser::getUrl(['record' => $server->user]))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('node.name')
                    ->hidden(fn () => $this->getOwnerRecord() instanceof Node)
                    ->icon('tabler-server-2')
                    ->url(fn (Server $server) => EditNode::getUrl(['record' => $server->node]))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('egg.name')
                    ->hidden(fn () => $this->getOwnerRecord() instanceof Egg)
                    ->icon('tabler-egg')
                    ->url(fn (Server $server) => EditEgg::getUrl(['record' => $server->egg]))
                    ->sortable()
                    ->searchable(),
                SelectColumn::make('allocation.id')
                    ->label('Primary Allocation')
                    ->options(fn (Server $server) => [$server->allocation->id => $server->allocation->address])
                    ->selectablePlaceholder(false)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('image')
                    ->label('Docker Image')
                    ->formatStateUsing(fn ($state, Server $server) => array_search($state, $server->egg->docker_images) ?: $state),
                TextColumn::make('cpu')
                    ->label('CPU')
                    ->icon('tabler-cpu')
                    ->suffix(' %'),
                TextColumn::make('memory')
                    ->icon('tabler-device-desktop-analytics')
                    ->formatStateUsing(fn ($state) => Number::format($state / (config('panel.use_binary_prefix') ? 1024 : 1000), maxPrecision: 2, locale: auth()->user()->language))
                    ->suffix(config('panel.use_binary_prefix') ? ' GiB' : ' GB'),
                TextColumn::make('disk')
                    ->icon('tabler-file')
                    ->formatStateUsing(fn ($state) => Number::format($state / (config('panel.use_binary_prefix') ? 1024 : 1000), maxPrecision: 2, locale: auth()->user()->language))
                    ->suffix(config('panel.use_binary_prefix') ? ' GiB' : ' GB'),
            ]);
    }
}

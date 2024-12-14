<?php

namespace App\Filament\Admin\RelationManagers;

use App\Filament\Admin\Resources\DatabaseHostResource\Pages\EditDatabaseHost;
use App\Filament\Admin\Resources\ServerResource\Pages\EditServer;
use App\Filament\Components\Forms\Actions\RotateDatabasePasswordAction;
use App\Models\Database;
use App\Filament\Components\Tables\Columns\DateTimeColumn;
use App\Models\DatabaseHost;
use App\Models\Server;
use App\Services\Databases\DatabaseManagementService;
use App\Services\Servers\RandomWordService;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DatabasesRelationManager extends RelationManager
{
    protected static string $relationship = 'databases';

    protected static ?string $icon = 'tabler-database';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('database')
                    ->columnSpanFull(),
                TextInput::make('username'),
                TextInput::make('password')
                    ->formatStateUsing(fn (Database $database) => $database->password)
                    ->password()
                    ->revealable()
                    ->hintAction(RotateDatabasePasswordAction::make()),
                TextInput::make('remote')
                    ->label('Connections From')
                    ->formatStateUsing(fn (Database $record) => $record->remote === '%' ? 'Anywhere' : $record->remote),
                TextInput::make('max_connections')
                    ->formatStateUsing(fn (?Database $record) => $record->max_connections === 0 ? 'Unlimited' : $record->max_connections),
                TextInput::make('jdbc')
                    ->label('JDBC Connection String')
                    ->formatStateUsing(fn (Database $database) => $database->jdbc)
                    ->columnSpanFull()
                    ->password()
                    ->revealable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('database')
            ->columns([
                TextColumn::make('database'),
                TextColumn::make('username'),
                TextColumn::make('remote')
                    ->label('Connections From')
                    ->formatStateUsing(fn (Database $record) => $record->remote === '%' ? 'Anywhere' : $record->remote),
                TextColumn::make('server.name')
                    ->hidden(fn () => $this->getOwnerRecord() instanceof Server)
                    ->icon('tabler-brand-docker')
                    ->url(fn (Database $database) => EditServer::getUrl(['record' => $database->server])),
                TextColumn::make('host.name')
                    ->hidden(fn () => $this->getOwnerRecord() instanceof DatabaseHost)
                    ->icon('tabler-database')
                    ->url(fn (Database $database) => EditDatabaseHost::getUrl(['record' => $database->host])),
                TextColumn::make('max_connections')
                    ->formatStateUsing(fn (Database $record) => $record->max_connections === 0 ? 'Unlimited' : $record->max_connections),
                DateTimeColumn::make('created_at'),
            ])
            ->actions([
                ViewAction::make()
                    ->color('primary')
                    ->authorize(fn () => auth()->user()->can('viewList database')),
                DeleteAction::make()
                    ->authorize(fn (Database $database) => auth()->user()->can('delete database', $database)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->createAnother(false)
                    ->visible(fn () => $this->getOwnerRecord() instanceof Server && DatabaseHost::whereRelation('nodes', 'nodes.id', $this->getOwnerRecord()->node_id)->count() >= 1)
                    ->form([
                        Select::make('database_host_id')
                            ->label('Database Host')
                            ->required()
                            ->placeholder('Select Database Host')
                            ->options(function () {
                                /** @var Server $server */
                                $server = $this->getOwnerRecord();

                                return DatabaseHost::whereRelation('nodes', 'nodes.id', $server->node_id)->get()->mapWithKeys(fn (DatabaseHost $databaseHost) => [$databaseHost->id => $databaseHost->name]);
                            }),
                        TextInput::make('database')
                            ->label('Database Name')
                            ->alphaDash()
                            ->prefix(function () {
                                /** @var Server $server */
                                $server = $this->getOwnerRecord();

                                return 's' . $server->id . '_';
                            })
                            ->hintIcon('tabler-question-mark')
                            ->hintIconTooltip('Leaving this blank will auto generate a random name'),
                        TextInput::make('remote')
                            ->columnSpan(1)
                            ->placeholder('Anywhere')
                            ->label('Connections From')
                            ->hintIcon('tabler-question-mark')
                            ->hintIconTooltip('Where connections should be allowed from. Leave blank to allow connections from anywhere.'),
                    ])
                    ->action(function (array $data, DatabaseManagementService $service, RandomWordService $randomWordService) {
                        /** @var Server $server */
                        $server = $this->getOwnerRecord();

                        $data['remote'] ??= '%';

                        $data['database'] ??= $randomWordService->word() . random_int(132, 420);
                        $data['database'] = $service->generateUniqueDatabaseName($data['database'], $server->id);

                        try {
                            $service->setValidateDatabaseLimit(false)->create($server, $data);
                        } catch (Exception $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Failed to create Database')
                                ->body($exception->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DatabaseHostResource\Pages;
use App\Filament\Admin\RelationManagers\DatabasesRelationManager;
use App\Models\DatabaseHost;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DatabaseHostResource extends Resource
{
    protected static ?string $model = DatabaseHost::class;

    protected static ?string $navigationIcon = 'tabler-database';

    protected static ?string $navigationGroup = 'Advanced';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->checkIfRecordIsSelectableUsing(fn (DatabaseHost $databaseHost) => !$databaseHost->databases_count)
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('host'),
                TextColumn::make('port'),
                TextColumn::make('username'),
                TextColumn::make('databases_count')
                    ->label('Databases')
                    ->counts('databases')
                    ->icon('tabler-database')
                    ->formatStateUsing(fn ($state, DatabaseHost $databaseHost) => $databaseHost->max_databases ? "$state of $databaseHost->max_databases" : $state),
                TextColumn::make('nodes.name')
                    ->badge()
                    ->icon('tabler-server-2')
                    ->placeholder('No Nodes'),
            ])
            ->actions([
                ViewAction::make()
                    ->hidden(fn (DatabaseHost $databaseHost) => self::canEdit($databaseHost)),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (DatabaseHost $databaseHost) => $databaseHost->databases_count > 0),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn () => auth()->user()->can('delete databasehost')),
                ]),
            ])
            ->emptyStateIcon(self::getNavigationIcon())
            ->emptyStateDescription('')
            ->emptyStateHeading('No Database Hosts')
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('filament-panels::resources/pages/create-record.title', ['label' => self::getTitleCaseModelLabel()])),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(['default' => 2, 'sm' => 3, 'md' => 3, 'lg' => 4])
                    ->schema([
                        TextInput::make('host')
                            ->required()
                            ->columnSpan(2)
                            ->helperText('The IP address or Domain name that should be used when attempting to connect to this MySQL host from this Panel to create new databases.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Set $set) => $set('name', $state))
                            ->maxLength(255),
                        TextInput::make('port')
                            ->required()
                            ->columnSpan(1)
                            ->helperText('The port that MySQL is running on for this host.')
                            ->numeric()
                            ->default(3306)
                            ->minValue(0)
                            ->maxValue(65535),
                        TextInput::make('max_databases')
                            ->label('Max databases')
                            ->placeholder('Unlimited')
                            ->numeric(),
                        TextInput::make('name')
                            ->required()
                            ->label('Display Name')
                            ->helperText('A short identifier used to distinguish this location from others. Must be between 1 and 60 characters, for example, us.nyc.lvl3.')
                            ->maxLength(60),
                        TextInput::make('username')
                            ->required()
                            ->helperText('The username of an account that has enough permissions to create new users and databases on the system.')
                            ->maxLength(255),
                        TextInput::make('password')
                            ->required()
                            ->helperText('The password for the database user.')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        Select::make('node_ids')
                            ->label('Linked Nodes')
                            ->relationship('nodes', 'name')
                            ->helperText('This setting only defaults to this database host when adding a database to a server on the selected node.')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DatabasesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDatabaseHosts::route('/'),
            'create' => Pages\CreateDatabaseHost::route('/create'),
            'view' => Pages\ViewDatabaseHost::route('/{record}'),
            'edit' => Pages\EditDatabaseHost::route('/{record}/edit'),
        ];
    }
}

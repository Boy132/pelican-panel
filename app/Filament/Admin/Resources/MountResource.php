<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MountResource\Pages;
use App\Models\Mount;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MountResource extends Resource
{
    protected static ?string $model = Mount::class;

    protected static ?string $navigationIcon = 'tabler-layers-linked';

    protected static ?string $navigationGroup = 'Advanced';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->description(fn (Mount $mount) => "$mount->source -> $mount->target")
                    ->sortable(),
                TextColumn::make('eggs.name')
                    ->icon('tabler-eggs')
                    ->label('Eggs')
                    ->badge()
                    ->placeholder('All eggs'),
                TextColumn::make('nodes.name')
                    ->icon('tabler-server-2')
                    ->label('Nodes')
                    ->badge()
                    ->placeholder('All nodes'),
                TextColumn::make('read_only')
                    ->label('Read only?')
                    ->badge()
                    ->icon(fn ($state) => $state ? 'tabler-writing-off' : 'tabler-writing')
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state ? 'Read only' : 'Writeable'),
                TextColumn::make('user_mountable')
                    ->disabled() // TODO: user mounts
                    ->label('User mountable?')
                    ->badge()
                    ->icon(fn ($state) => $state ? 'tabler-user-bolt' : 'tabler-user-cancel')
                    ->color(fn ($state) => $state ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
            ])
            ->actions([
                ViewAction::make()
                    ->hidden(fn (Mount $mount) => self::canEdit($mount)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn () => auth()->user()->can('delete mount')),
                ]),
            ])
            ->emptyStateIcon(self::getNavigationIcon())
            ->emptyStateDescription('')
            ->emptyStateHeading('No Mounts')
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('filament-panels::resources/pages/create-record.title', ['label' => self::getTitleCaseModelLabel()])),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(['default' => 1, 'lg' => 2])
            ->schema([
                Section::make()->schema([
                    TextInput::make('name')
                        ->required()
                        ->helperText('Unique name used to separate this mount from another.')
                        ->maxLength(64)
                        ->columnSpanFull(),
                    ToggleButtons::make('read_only')
                        ->label('Read only?')
                        ->helperText('Is the mount read only inside the container?')
                        ->options([
                            false => 'Writeable',
                            true => 'Read only',
                        ])
                        ->icons([
                            false => 'tabler-writing',
                            true => 'tabler-writing-off',
                        ])
                        ->colors([
                            false => 'warning',
                            true => 'success',
                        ])
                        ->inline()
                        ->default(false)
                        ->required(),
                    ToggleButtons::make('user_mountable')
                        ->disabled() // TODO: user mounts
                        ->label('User mountable?')
                        ->helperText('Should users be allowed to enable/ disable this mount?')
                        ->options([
                            false => 'No',
                            true => 'Yes',
                        ])
                        ->icons([
                            false => 'tabler-user-cancel',
                            true => 'tabler-user-bolt',
                        ])
                        ->colors([
                            false => 'success',
                            true => 'warning',
                        ])
                        ->inline()
                        ->default(false)
                        ->required(),
                    TextInput::make('source')
                        ->required()
                        ->helperText('File path on the host system to mount to a container.')
                        ->maxLength(255),
                    TextInput::make('target')
                        ->required()
                        ->helperText('Where the mount will be accessible inside a container.')
                        ->maxLength(255),
                    Textarea::make('description')
                        ->helperText('A longer description for this mount.')
                        ->columnSpanFull(),
                ])->columnSpan(1)->columns(['default' => 1, 'lg' => 2]),
                Group::make()->schema([
                    Section::make()->schema([
                        Select::make('eggs')
                            ->relationship('eggs', 'name')
                            ->multiple()
                            ->preload(),
                        Select::make('nodes')
                            ->relationship('nodes', 'name')
                            ->multiple()
                            ->preload(),
                    ]),
                ])->columns(['default' => 1, 'lg' => 2]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMounts::route('/'),
            'create' => Pages\CreateMount::route('/create'),
            'view' => Pages\ViewMount::route('/{record}'),
            'edit' => Pages\EditMount::route('/{record}/edit'),
        ];
    }
}

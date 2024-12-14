<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\RelationManagers\ServersRelationManager;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use DateTimeZone;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'tabler-users';

    protected static ?string $recordTitleAttribute = 'username';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('picture')
                    ->visibleFrom('lg')
                    ->label('')
                    ->extraImgAttributes(['class' => 'rounded-full'])
                    ->defaultImageUrl(fn (User $user) => 'https://gravatar.com/avatar/' . md5($user->email)),
                TextColumn::make('username')
                    ->searchable()
                    ->icon('tabler-user'),
                TextColumn::make('email')
                    ->searchable()
                    ->icon('tabler-mail'),
                IconColumn::make('use_totp')
                    ->label('2FA')
                    ->visibleFrom('lg')
                    ->icon(fn (User $user) => $user->use_totp ? 'tabler-lock' : 'tabler-lock-open-off')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->icon('tabler-users-group')
                    ->placeholder('No roles'),
                TextColumn::make('servers_count')
                    ->counts('servers')
                    ->icon('tabler-server')
                    ->label('Servers'),
                TextColumn::make('subusers_count')
                    ->visibleFrom('sm')
                    ->label('Subusers')
                    ->counts('subusers')
                    ->icon('tabler-users'),
            ])
            ->actions([
                ViewAction::make()
                    ->hidden(fn (User $user) => self::canEdit($user)),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $user) => auth()->user()->id === $user->id || $user->servers_count > 0),
            ])
            ->checkIfRecordIsSelectableUsing(fn (User $user) => auth()->user()->id !== $user->id && !$user->servers_count)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn () => auth()->user()->can('delete user')),
                ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')
                    ->alphaNum()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->minLength(3)
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->prefixIcon('tabler-mail')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->prefixIcon('tabler-password')
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->password()
                    ->revealable(fn (string $operation) => $operation === 'create'),
                Select::make('timezone')
                    ->required()
                    ->prefixIcon('tabler-clock-pin')
                    ->default(env('APP_TIMEZONE', 'UTC'))
                    ->options(fn () => collect(DateTimeZone::listIdentifiers())->mapWithKeys(fn ($tz) => [$tz => $tz])),
                Select::make('language')
                    ->required()
                    ->prefixIcon('tabler-flag')
                    ->default(env('APP_LOCALE', 'en'))
                    ->options(fn (User $user) => $user->getAvailableLanguages()),
                CheckboxList::make('roles')
                    ->disabled(fn (User $user) => $user->id === auth()->user()->id)
                    ->disableOptionWhen(fn (string $value) => $value == Role::getRootAdmin()->id)
                    ->relationship('roles', 'name')
                    ->label('Admin Roles')
                    ->columnSpanFull()
                    ->bulkToggleable(false),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ServersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ApiKeyResource\Pages;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Models\ApiKey;
use App\Services\Acl\Api\AdminAcl;
use App\Filament\Components\Tables\Columns\DateTimeColumn;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static ?string $modelLabel = 'Application API Key';

    protected static ?string $pluralModelLabel = 'Application API Keys';

    protected static ?string $navigationLabel = 'API Keys';

    protected static ?string $navigationIcon = 'tabler-key';

    protected static ?string $navigationGroup = 'Advanced';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('key_type', ApiKey::TYPE_APPLICATION)->count() ?: null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('key_type', ApiKey::TYPE_APPLICATION);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->copyable()
                    ->tooltip('Click to copy')
                    ->icon('tabler-clipboard-text')
                    ->state(fn (ApiKey $key) => $key->identifier . $key->token),
                TextColumn::make('memo')
                    ->label('Description')
                    ->wrap()
                    ->limit(50),
                DateTimeColumn::make('last_used_at')
                    ->label('Last Used')
                    ->placeholder('Not Used')
                    ->sortable(),
                DateTimeColumn::make('created_at')
                    ->label('Created')
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('Created By')
                    ->icon('tabler-user')
                    ->url(fn (ApiKey $apiKey) => EditUser::getUrl(['record' => $apiKey->user])),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->emptyStateIcon(self::getNavigationIcon())
            ->emptyStateDescription('')
            ->emptyStateHeading('No Application API Keys')
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('filament-panels::resources/pages/create-record.title', ['label' => self::getTitleCaseModelLabel()])),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Permissions')
                    ->columns(['default' => 1, 'sm' => 1, 'md' => 2])
                    ->schema(
                        collect(ApiKey::getPermissionList())->map(fn (string $resource) => ToggleButtons::make('permissions_' . $resource)
                            ->required()
                            ->label(str($resource)->replace('_', ' ')->title())
                            ->inline()
                            ->default(AdminAcl::NONE)
                            ->options([
                                AdminAcl::NONE => 'None',
                                AdminAcl::READ => 'Read',
                                AdminAcl::READ | AdminAcl::WRITE => 'Read & Write',
                            ])
                            ->icons([
                                AdminAcl::NONE => 'tabler-book-off',
                                AdminAcl::READ => 'tabler-book',
                                AdminAcl::READ | AdminAcl::WRITE => 'tabler-writing',
                            ])
                            ->colors([
                                AdminAcl::NONE => 'success',
                                AdminAcl::READ => 'warning',
                                AdminAcl::READ | AdminAcl::WRITE => 'danger',
                            ])
                            ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1]),
                        )->all(),
                    ),
                TagsInput::make('allowed_ips')
                    ->label('Whitelisted IPv4 Addresses')
                    ->placeholder('Example: 127.0.0.1 or 192.168.1.1')
                    ->helperText('Press enter to add a new IP address or leave blank to allow any IP address')
                    ->columnSpanFull(),
                Textarea::make('memo')
                    ->required()
                    ->label('Description')
                    ->helperText('
                        Once you have assigned permissions and created this set of credentials you will be unable to come back and edit it.
                        If you need to make changes down the road you will need to create a new set of credentials.
                    ')
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiKeys::route('/'),
            'create' => Pages\CreateApiKey::route('/create'),
        ];
    }
}

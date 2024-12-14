<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\RelationManagers\ServersRelationManager;
use App\Filament\Admin\Resources\NodeResource\Pages;
use App\Filament\Admin\Resources\NodeResource\RelationManagers\AllocationsRelationManager;
use App\Filament\Components\Tables\Columns\NodeHealthColumn;
use App\Models\Node;
use App\Services\Helpers\SoftwareVersionService;
use App\Services\Nodes\NodeAutoDeployService;
use App\Services\Nodes\NodeUpdateService;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Webbingbrasil\FilamentCopyActions\Forms\Actions\CopyAction;

class NodeResource extends Resource
{
    protected static ?string $model = Node::class;

    protected static ?string $navigationIcon = 'tabler-server-2';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->checkIfRecordIsSelectableUsing(fn (Node $node) => $node->servers_count <= 0)
            ->columns([
                NodeHealthColumn::make('health'),
                TextColumn::make('name')
                    ->icon('tabler-server-2')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('fqdn')
                    ->visibleFrom('md')
                    ->label('Address')
                    ->icon('tabler-network')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('memory')
                    ->visibleFrom('sm')
                    ->icon('tabler-device-desktop-analytics')
                    ->numeric()
                    ->suffix(config('panel.use_binary_prefix') ? ' GiB' : ' GB')
                    ->formatStateUsing(fn ($state) => Number::format($state / (config('panel.use_binary_prefix') ? 1024 : 1000), maxPrecision: 2, locale: auth()->user()->language))
                    ->sortable(),
                TextColumn::make('disk')
                    ->visibleFrom('sm')
                    ->icon('tabler-file')
                    ->numeric()
                    ->suffix(config('panel.use_binary_prefix') ? ' GiB' : ' GB')
                    ->formatStateUsing(fn ($state) => Number::format($state / (config('panel.use_binary_prefix') ? 1024 : 1000), maxPrecision: 2, locale: auth()->user()->language))
                    ->sortable(),
                TextColumn::make('cpu')
                    ->visibleFrom('sm')
                    ->label('CPU')
                    ->icon('tabler-cpu')
                    ->numeric()
                    ->suffix(' %')
                    ->sortable(),
                IconColumn::make('scheme')
                    ->visibleFrom('xl')
                    ->label('SSL')
                    ->trueIcon('tabler-lock')
                    ->falseIcon('tabler-lock-open-off')
                    ->state(fn (Node $node) => $node->scheme === 'https'),
                IconColumn::make('public')
                    ->visibleFrom('lg')
                    ->trueIcon('tabler-eye-check')
                    ->falseIcon('tabler-eye-cancel'),
                TextColumn::make('servers_count')
                    ->visibleFrom('sm')
                    ->counts('servers')
                    ->label('Servers')
                    ->icon('tabler-brand-docker')
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make()
                    ->hidden(fn (Node $node) => self::canEdit($node)),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Node $node) => $node->servers_count > 0),
            ])
            ->emptyStateIcon(self::getNavigationIcon())
            ->emptyStateDescription('')
            ->emptyStateHeading('No Nodes')
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('filament-panels::resources/pages/create-record.title', ['label' => self::getTitleCaseModelLabel()])),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(fn ($livewire) => $livewire instanceof CreateRecord ? self::createSchema() : self::editSchema());
    }

    private static function createSchema(): array
    {
        return [
            Wizard::make([
                Step::make('basic')
                    ->label('Basic Settings')
                    ->icon('tabler-server')
                    ->columnSpanFull()
                    ->columns(['default' => 2, 'sm' => 3, 'md' => 3, 'lg' => 4])
                    ->schema(self::basicSettingsTab()),
                Step::make('advanced')
                    ->label('Advanced Settings')
                    ->icon('tabler-server-cog')
                    ->columnSpanFull()
                    ->columns(['default' => 2, 'sm' => 3, 'md' => 3, 'lg' => 4])
                    ->schema(self::advancedSettingsTab(true)),
            ])->columnSpanFull()
                ->nextAction(fn (Action $action) => $action->label('Next Step'))
                ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                    <x-filament::button
                            type="submit"
                            size="sm"
                        >
                            Create Node
                        </x-filament::button>
                    BLADE))),
        ];
    }

    private static function editSchema(): array
    {
        return [
            Tabs::make('Tabs')
                ->columns(['default' => 2, 'sm' => 3, 'md' => 3, 'lg' => 4])
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Tab::make('')
                        ->label('Overview')
                        ->icon('tabler-chart-area-line-filled')
                        ->columns(4)
                        ->schema([
                            Fieldset::make()
                                ->label('Node Information')
                                ->columns(4)
                                ->schema([
                                    Placeholder::make('')
                                        ->label('Wings Version')
                                        ->content(fn (Node $node, SoftwareVersionService $versionService) => ($node->systemInformation()['version'] ?? 'Unknown') . ' (Latest: ' . $versionService->latestWingsVersion() . ')'),
                                    Placeholder::make('')
                                        ->label('CPU Threads')
                                        ->content(fn (Node $node) => $node->systemInformation()['cpu_count'] ?? 0),
                                    Placeholder::make('')
                                        ->label('Architecture')
                                        ->content(fn (Node $node) => $node->systemInformation()['architecture'] ?? 'Unknown'),
                                    Placeholder::make('')
                                        ->label('Kernel')
                                        ->content(fn (Node $node) => $node->systemInformation()['kernel_version'] ?? 'Unknown'),
                                ]),
                            View::make('filament.components.node-cpu-chart')
                                ->columnSpan(['default' => 4, 'sm' => 1, 'md' => 2, 'lg' => 2]),
                            View::make('filament.components.node-memory-chart')
                                ->columnSpan(['default' => 4, 'sm' => 1, 'md' => 2, 'lg' => 2]),
                            // View::make('filament.components.node-storage-chart')->columnSpan(3), // TODO: make pretty
                        ]),
                    Tab::make('Basic Settings')
                        ->icon('tabler-server')
                        ->schema(self::basicSettingsTab()),
                    Tab::make('Advanced Settings')
                        ->columns(['default' => 1, 'sm' => 1, 'md' => 4, 'lg' => 6])
                        ->icon('tabler-server-cog')
                        ->schema(self::advancedSettingsTab(false)),
                    Tab::make('Configuration File')
                        ->icon('tabler-code')
                        ->schema([
                            Placeholder::make('instructions')
                                ->columnSpanFull()
                                ->content(new HtmlString('
                                  Save this file to your <span title="usually /etc/pelican/">daemon\'s root directory</span>, named <code>config.yml</code>
                            ')),
                            Textarea::make('config')
                                ->label('/etc/pelican/config.yml')
                                ->disabled()
                                ->rows(19)
                                ->hintAction(CopyAction::make())
                                ->columnSpanFull(),
                            Grid::make()
                                ->columns()
                                ->hiddenOn('view')
                                ->schema([
                                    FormActions::make([
                                        FormActions\Action::make('autoDeploy')
                                            ->label('Auto Deploy Command')
                                            ->color('primary')
                                            ->icon('tabler-rocket')
                                            ->modalHeading('Auto Deploy Command')
                                            ->modalSubmitAction(false)
                                            ->modalCancelAction(false)
                                            ->modalFooterActionsAlignment(Alignment::Center)
                                            ->form([
                                                ToggleButtons::make('docker')
                                                    ->label('Type')
                                                    ->live()
                                                    ->helperText('Choose between Standalone and Docker install.')
                                                    ->inline()
                                                    ->default(false)
                                                    ->afterStateUpdated(fn (bool $state, NodeAutoDeployService $service, Node $node, Set $set) => $set('generatedToken', $service->handle(request(), $node, $state)))
                                                    ->options([
                                                        false => 'Standalone',
                                                        true => 'Docker',
                                                    ])
                                                    ->colors([
                                                        false => 'primary',
                                                        true => 'success',
                                                    ])
                                                    ->columnSpan(1),
                                                Textarea::make('generatedToken')
                                                    ->label('To auto-configure your node run the following command:')
                                                    ->readOnly()
                                                    ->autosize()
                                                    ->hintAction(fn (string $state) => CopyAction::make()->copyable($state))
                                                    ->formatStateUsing(fn (NodeAutoDeployService $service, Node $node, Set $set, Get $get) => $set('generatedToken', $service->handle(request(), $node, $get('docker')))),
                                            ])
                                            ->mountUsing(fn (Form $form) => $form->fill()),
                                    ])->fullWidth(),
                                    FormActions::make([
                                        FormActions\Action::make('resetKey')
                                            ->label('Reset Daemon Token')
                                            ->color('danger')
                                            ->requiresConfirmation()
                                            ->modalHeading('Reset Daemon Token?')
                                            ->modalDescription('Resetting the daemon token will void any request coming from the old token. This token is used for all sensitive operations on the daemon including server creation and deletion. We suggest changing this token regularly for security.')
                                            ->action(function (NodeUpdateService $nodeUpdateService, Node $node) {
                                                $nodeUpdateService->handle($node, [], true);

                                                Notification::make()
                                                    ->success()
                                                    ->title('Daemon Key Reset')
                                                    ->send();
                                            }),
                                    ])->fullWidth(),
                                ]),
                        ]),
                ]),
        ];
    }

    private static function basicSettingsTab(): array
    {
        return [
            TextInput::make('fqdn')
                ->columnSpan(2)
                ->required()
                ->autofocus()
                ->live(debounce: 1500)
                ->rule('prohibited', fn ($state) => is_ip($state) && request()->isSecure())
                ->label(fn ($state) => is_ip($state) ? 'IP Address' : 'Domain Name')
                ->placeholder(fn ($state) => is_ip($state) ? '255.255.255.255' : 'node.example.com')
                ->helperText(function ($state) {
                    if (is_ip($state)) {
                        if (request()->isSecure()) {
                            return '
                                    Your panel is currently secured via an SSL certificate and that means your nodes require one too.
                                    You must use a domain name, because you cannot get SSL certificates for IP Addresses
                                ';
                        }

                        return '';
                    }

                    return "
                            This is the domain name that points to your node's IP Address.
                            If you've already set up this, you can verify it by checking the next field!
                            ";
                })
                ->hintColor('danger')
                ->hint(function ($state) {
                    if (is_ip($state) && request()->isSecure()) {
                        return 'You cannot connect to an IP Address over SSL';
                    }

                    return '';
                })
                ->afterStateUpdated(function (Set $set, ?string $state) {
                    $set('dns', null);
                    $set('ip', null);

                    [$subdomain] = str($state)->explode('.', 2);
                    if (!is_numeric($subdomain)) {
                        $set('name', $subdomain);
                    }

                    if (!$state || is_ip($state)) {
                        $set('dns', null);

                        return;
                    }

                    $validRecords = gethostbynamel($state);
                    if ($validRecords) {
                        $set('dns', true);

                        $set('ip', collect($validRecords)->first());

                        return;
                    }

                    $set('dns', false);
                })
                ->maxLength(255),
            TextInput::make('ip')
                ->disabled()
                ->hidden(),
            ToggleButtons::make('dns')
                ->label('DNS Record Check')
                ->helperText('This lets you know if your DNS record correctly points to an IP Address.')
                ->disabled()
                ->inline()
                ->default(null)
                ->hint(fn (Get $get) => $get('ip'))
                ->hintColor('success')
                ->options([
                    true => 'Valid',
                    false => 'Invalid',
                ])
                ->colors([
                    true => 'success',
                    false => 'danger',
                ])
                ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 1]),
            TextInput::make('daemon_listen')
                ->label('Port')
                ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 1])
                ->helperText('If you are running the daemon behind Cloudflare you should set the daemon port to 8443 to allow websocket proxying over SSL.')
                ->minValue(1)
                ->maxValue(65535)
                ->default(8080)
                ->required()
                ->integer(),
            TextInput::make('name')
                ->label('Display Name')
                ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 2])
                ->required()
                ->helperText('This name is for display only and can be changed later.')
                ->maxLength(100),
            ToggleButtons::make('scheme')
                ->label('Communicate over SSL')
                ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 1])
                ->inline()
                ->helperText(function (Get $get) {
                    if (request()->isSecure()) {
                        return new HtmlString('Your Panel is using a secure SSL connection,<br>so your Daemon must too.');
                    }

                    if (is_ip($get('fqdn'))) {
                        return 'An IP address cannot use SSL.';
                    }

                    return '';
                })
                ->disableOptionWhen(fn (string $value): bool => $value === 'http' && request()->isSecure())
                ->options([
                    'http' => 'HTTP',
                    'https' => 'HTTPS (SSL)',
                ])
                ->colors([
                    'http' => 'warning',
                    'https' => 'success',
                ])
                ->icons([
                    'http' => 'tabler-lock-open-off',
                    'https' => 'tabler-lock',
                ])
                ->default(fn () => request()->isSecure() ? 'https' : 'http'),
        ];
    }

    private static function advancedSettingsTab(bool $create): array
    {
        return [
            TextInput::make('id')
                ->label('Node ID')
                ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 1])
                ->disabled()
                ->hiddenOn('create'),
            TextInput::make('uuid')
                ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2])
                ->label('Node UUID')
                ->hintAction(CopyAction::make())
                ->disabled()
                ->hiddenOn('create'),
            TextInput::make('daemon_sftp_alias')
                ->columnSpan($create ? 2 : ['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2])
                ->label('SFTP Alias')
                ->helperText('Display alias for the SFTP address. Leave empty to use the Node FQDN.'),
            TextInput::make('daemon_sftp')
                ->columnSpan(1)
                ->label('SFTP Port')
                ->minValue(1)
                ->maxValue(65535)
                ->default(2022)
                ->required()
                ->integer(),
            TextInput::make('upload_size')
                ->columnSpan($create ? 1 : ['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 1])
                ->label('Upload Limit')
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip('Enter the maximum size of files that can be uploaded through the web-based file manager.')
                ->numeric()->required()
                ->minValue(1)
                ->maxValue(1024)
                ->default(256)
                ->suffix(config('panel.use_binary_prefix') ? 'MiB' : 'MB'),
            TagsInput::make('tags')
                ->columnSpan($create ? 2 : ['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 3])
                ->placeholder('Add Tags'),
            ToggleButtons::make('public')
                ->columnSpan(1)
                ->label('Use Node for deployment?')
                ->inline()
                ->options([
                    true => 'Yes',
                    false => 'No',
                ])
                ->colors([
                    true => 'success',
                    false => 'danger',
                ])
                ->default(true),
            ToggleButtons::make('maintenance_mode')
                ->columnSpan(1)
                ->label('Maintenance Mode')
                ->inline()
                ->hinticon('tabler-question-mark')
                ->hintIconTooltip("If the node is marked 'Under Maintenance' users won't be able to access servers that are on this node.")
                ->options([
                    false => 'Disable',
                    true => 'Enable',
                ])
                ->colors([
                    false => 'success',
                    true => 'danger',
                ])
                ->default(false),
            Grid::make()
                ->columns(['default' => 1, 'sm' => 1, 'md' => 3, 'lg' => 6])
                ->columnSpanFull()
                ->schema([
                    ToggleButtons::make('unlimited_mem')
                        ->label('Memory')
                        ->inlineLabel()
                        ->inline()
                        ->afterStateUpdated(fn (Set $set) => $set('memory', 0))
                        ->afterStateUpdated(fn (Set $set) => $set('memory_overallocate', 0))
                        ->formatStateUsing(fn (Get $get) => $get('memory') == 0)
                        ->live()
                        ->options([
                            true => 'Unlimited',
                            false => 'Limited',
                        ])
                        ->colors([
                            true => 'primary',
                            false => 'warning',
                        ])
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 2]),
                    TextInput::make('memory')
                        ->dehydratedWhenHidden()
                        ->hidden(fn (Get $get) => $get('unlimited_mem'))
                        ->label('Memory Limit')
                        ->inlineLabel()
                        ->suffix(config('panel.use_binary_prefix') ? 'MiB' : 'MB')
                        ->required()
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 2])
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('memory_overallocate')
                        ->dehydratedWhenHidden()
                        ->label('Overallocate')
                        ->inlineLabel()
                        ->required()
                        ->hidden(fn (Get $get) => $get('unlimited_mem'))
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('The % allowable to go over the set limit.')
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 2])
                        ->numeric()
                        ->minValue(-1)
                        ->maxValue(100)
                        ->suffix('%'),
                ]),
            Grid::make()
                ->columns(['default' => 1, 'sm' => 1, 'md' => 3, 'lg' => 6])
                ->schema([
                    ToggleButtons::make('unlimited_disk')
                        ->label('Disk')
                        ->inlineLabel()
                        ->inline()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('disk', 0))
                        ->afterStateUpdated(fn (Set $set) => $set('disk_overallocate', 0))
                        ->formatStateUsing(fn (Get $get) => $get('disk') == 0)
                        ->options([
                            true => 'Unlimited',
                            false => 'Limited',
                        ])
                        ->colors([
                            true => 'primary',
                            false => 'warning',
                        ])
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 2]),
                    TextInput::make('disk')
                        ->dehydratedWhenHidden()
                        ->hidden(fn (Get $get) => $get('unlimited_disk'))
                        ->label('Disk Limit')
                        ->inlineLabel()
                        ->suffix(config('panel.use_binary_prefix') ? 'MiB' : 'MB')
                        ->required()
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 2])
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('disk_overallocate')
                        ->dehydratedWhenHidden()
                        ->hidden(fn (Get $get) => $get('unlimited_disk'))
                        ->label('Overallocate')
                        ->inlineLabel()
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('The % allowable to go over the set limit.')
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 2])
                        ->required()
                        ->numeric()
                        ->minValue(-1)
                        ->maxValue(100)
                        ->suffix('%'),
                ]),
            Grid::make()
                ->columns(6)
                ->columnSpanFull()
                ->schema([
                    ToggleButtons::make('unlimited_cpu')
                        ->label('CPU')
                        ->inlineLabel()
                        ->inline()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('cpu', 0))
                        ->afterStateUpdated(fn (Set $set) => $set('cpu_overallocate', 0))
                        ->formatStateUsing(fn (Get $get) => $get('cpu') == 0)
                        ->options([
                            true => 'Unlimited',
                            false => 'Limited',
                        ])
                        ->colors([
                            true => 'primary',
                            false => 'warning',
                        ])
                        ->columnSpan(2),
                    TextInput::make('cpu')
                        ->dehydratedWhenHidden()
                        ->hidden(fn (Get $get) => $get('unlimited_cpu'))
                        ->label('CPU Limit')->inlineLabel()
                        ->suffix('%')
                        ->required()
                        ->columnSpan(2)
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('cpu_overallocate')
                        ->dehydratedWhenHidden()
                        ->hidden(fn (Get $get) => $get('unlimited_cpu'))
                        ->label('Overallocate')->inlineLabel()
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('The % allowable to go over the set limit.')
                        ->columnSpan(2)
                        ->required()
                        ->numeric()
                        ->minValue(-1)
                        ->maxValue(100)
                        ->suffix('%'),
                ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            AllocationsRelationManager::class,
            ServersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNodes::route('/'),
            'create' => Pages\CreateNode::route('/create'),
            'view' => Pages\ViewNode::route('/{record}'),
            'edit' => Pages\EditNode::route('/{record}/edit'),
        ];
    }
}

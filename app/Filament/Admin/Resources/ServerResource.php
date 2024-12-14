<?php

namespace App\Filament\Admin\Resources;

use App\Enums\ContainerStatus;
use App\Enums\ServerState;
use App\Filament\Admin\RelationManagers\DatabasesRelationManager;
use App\Filament\Admin\Resources\EggResource\Pages\EditEgg;
use App\Filament\Admin\Resources\NodeResource\Pages\EditNode;
use App\Filament\Admin\Resources\ServerResource\Pages;
use App\Filament\Admin\Resources\ServerResource\RelationManagers\AllocationsRelationManager;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Filament\Server\Pages\Console;
use App\Models\Egg;
use App\Models\Mount;
use App\Models\Server;
use App\Models\ServerVariable;
use App\Services\Eggs\EggChangerService;
use App\Services\Servers\RandomWordService;
use App\Services\Servers\ReinstallServerService;
use App\Services\Servers\SuspensionService;
use App\Services\Servers\ToggleInstallService;
use App\Services\Servers\TransferServerService;
use Closure;
use Exception;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormsAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Number;
use LogicException;
use Webbingbrasil\FilamentCopyActions\Forms\Actions\CopyAction;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'tabler-brand-docker';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('node.name')
            ->groups([
                Group::make('node.name')
                    ->getDescriptionFromRecordUsing(fn (Server $server) => str($server->node->description)->limit(150)),
                Group::make('user.username')
                    ->getDescriptionFromRecordUsing(fn (Server $server) => $server->user->email),
                Group::make('egg.name')
                    ->getDescriptionFromRecordUsing(fn (Server $server) => str($server->egg->description)->limit(150)),
            ])
            ->columns([
                TextColumn::make('condition')
                    ->default('unknown')
                    ->badge()
                    ->icon(fn (Server $server) => $server->conditionIcon())
                    ->color(fn (Server $server) => $server->conditionColor()),
                TextColumn::make('name')
                    ->icon('tabler-brand-docker')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('node.name')
                    ->icon('tabler-server-2')
                    ->url(fn (Server $server) => EditNode::getUrl(['record' => $server->node]))
                    ->hidden(fn (Table $table) => $table->getGrouping()?->getId() === 'node.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('egg.name')
                    ->icon('tabler-egg')
                    ->url(fn (Server $server) => EditEgg::getUrl(['record' => $server->egg]))
                    ->hidden(fn (Table $table) => $table->getGrouping()?->getId() === 'egg.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.username')
                    ->icon('tabler-user')
                    ->label('Owner')
                    ->url(fn (Server $server) => EditUser::getUrl(['record' => $server->user]))
                    ->hidden(fn (Table $table) => $table->getGrouping()?->getId() === 'user.username')
                    ->sortable()
                    ->searchable(),
                SelectColumn::make('allocation_id')
                    ->label('Primary Allocation')
                    ->visible(fn () => auth()->user()->can('update server'))
                    ->options(fn (Server $server) => $server->allocations->mapWithKeys(fn ($allocation) => [$allocation->id => $allocation->address]))
                    ->selectablePlaceholder(false)
                    ->sortable(),
                TextColumn::make('allocation_id_readonly')
                    ->label('Primary Allocation')
                    ->hidden(fn () => auth()->user()->can('update server'))
                    ->state(fn (Server $server) => $server->allocation->address),
                TextColumn::make('cpu')
                    ->label('CPU')
                    ->icon('tabler-cpu')
                    ->suffix(' %')
                    ->visibleFrom('xl'),
                TextColumn::make('memory')
                    ->icon('tabler-device-desktop-analytics')
                    ->formatStateUsing(fn ($state) => Number::format($state / (config('panel.use_binary_prefix') ? 1024 : 1000), maxPrecision: 2, locale: auth()->user()->language))
                    ->suffix(config('panel.use_binary_prefix') ? ' GiB' : ' GB')
                    ->visibleFrom('xl'),
                TextColumn::make('disk')
                    ->icon('tabler-file')
                    ->formatStateUsing(fn ($state) => Number::format($state / (config('panel.use_binary_prefix') ? 1024 : 1000), maxPrecision: 2, locale: auth()->user()->language))
                    ->suffix(config('panel.use_binary_prefix') ? ' GiB' : ' GB')
                    ->visibleFrom('xl'),
            ])
            ->actions([
                Action::make('console')
                    ->icon('tabler-terminal')
                    ->url(fn (Server $server) => Console::getUrl(panel: 'server', tenant: $server))
                    ->authorize(fn (Server $server) => auth()->user()->canAccessTenant($server)),
                ViewAction::make()
                    ->hidden(fn (Server $server) => self::canEdit($server)),
                EditAction::make(),
            ])
            ->emptyStateIcon(self::getNavigationIcon())
            ->emptyStateDescription('')
            ->emptyStateHeading('No Servers')
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('filament-panels::resources/pages/create-record.title', ['label' => self::getTitleCaseModelLabel()])),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->persistTabInQueryString()
                ->columns(['default' => 2, 'sm' => 2, 'md' => 4, 'lg' => 6])
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Information')
                        ->icon('tabler-info-circle')
                        ->schema([
                            TextInput::make('name')
                                ->prefixIcon('tabler-server')
                                ->label('Display Name')
                                ->suffixAction(
                                    FormsAction::make('random')
                                        ->hidden(fn ($operation) => $operation === 'view')
                                        ->icon('tabler-dice-' . random_int(1, 6))
                                        ->action(function (Set $set, Get $get) {
                                            $egg = Egg::find($get('egg_id'));
                                            $prefix = $egg ? str($egg->name)->lower()->kebab() . '-' : '';

                                            $word = (new RandomWordService())->word();

                                            $set('name', $prefix . $word);
                                        })
                                )
                                ->columnSpan(['default' => 2, 'sm' => 1, 'md' => 2, 'lg' => 3])
                                ->required()
                                ->maxLength(255),
                            Select::make('owner_id')
                                ->prefixIcon('tabler-user')
                                ->label('Owner')
                                ->columnSpan(['default' => 2, 'sm' => 1, 'md' => 2, 'lg' => 2])
                                ->relationship('user', 'username')
                                ->searchable()
                                ->preload()
                                ->required(),
                            ToggleButtons::make('condition')
                                ->label('Server Status')
                                ->formatStateUsing(fn (Server $server) => $server->condition)
                                ->options(fn ($state) => collect(array_merge(ContainerStatus::cases(), ServerState::cases()))
                                    ->filter(fn ($condition) => $condition->value === $state)
                                    ->mapWithKeys(fn ($state) => [$state->value => str($state->value)->replace('_', ' ')->ucwords()])
                                )
                                ->colors(collect(array_merge(ContainerStatus::cases(), ServerState::cases()))->mapWithKeys(
                                    fn ($status) => [$status->value => $status->color()]
                                ))
                                ->icons(collect(array_merge(ContainerStatus::cases(), ServerState::cases()))->mapWithKeys(
                                    fn ($status) => [$status->value => $status->icon()]
                                ))
                                ->columnSpan(['default' => 2, 'sm' => 1, 'md' => 1, 'lg' => 1]),
                            Textarea::make('description')
                                ->columnSpanFull(),
                            TextInput::make('uuid')
                                ->hintAction(CopyAction::make())
                                ->columnSpan(['default' => 2, 'sm' => 1, 'md' => 2, 'lg' => 3])
                                ->readOnly()
                                ->dehydrated(false),
                            TextInput::make('uuid_short')
                                ->label('Short UUID')
                                ->hintAction(CopyAction::make())
                                ->columnSpan(['default' => 2, 'sm' => 1, 'md' => 2, 'lg' => 3])
                                ->readOnly()
                                ->dehydrated(false),
                            TextInput::make('external_id')
                                ->label('External ID')
                                ->columnSpan(['default' => 2, 'sm' => 1, 'md' => 2, 'lg' => 3])
                                ->maxLength(255),
                            Select::make('node_id')
                                ->label('Node')
                                ->relationship('node', 'name')
                                ->columnSpan(['default' => 2, 'sm' => 1, 'md' => 2, 'lg' => 3])
                                ->disabled(),
                        ]),
                    Tab::make('Environment')
                        ->icon('tabler-brand-docker')
                        ->schema([
                            Fieldset::make('Resource Limits')
                                ->columns(['default' => 1, 'sm' => 2, 'md' => 3, 'lg' => 3])
                                ->schema([
                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('unlimited_mem')
                                                ->label('Memory')
                                                ->inlineLabel()
                                                ->inline()
                                                ->afterStateUpdated(fn (Set $set) => $set('memory', 0))
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
                                                ->columnSpan(2),
                                            TextInput::make('memory')
                                                ->dehydratedWhenHidden()
                                                ->hidden(fn (Get $get) => $get('unlimited_mem'))
                                                ->label('Memory Limit')
                                                ->inlineLabel()
                                                ->suffix(config('panel.use_binary_prefix') ? 'MiB' : 'MB')
                                                ->required()
                                                ->columnSpan(2)
                                                ->numeric()
                                                ->minValue(0),
                                        ]),
                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('unlimited_disk')
                                                ->label('Disk Space')
                                                ->inlineLabel()
                                                ->inline()
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set) => $set('disk', 0))
                                                ->formatStateUsing(fn (Get $get) => $get('disk') == 0)
                                                ->options([
                                                    true => 'Unlimited',
                                                    false => 'Limited',
                                                ])
                                                ->colors([
                                                    true => 'primary',
                                                    false => 'warning',
                                                ])
                                                ->columnSpan(2),
                                            TextInput::make('disk')
                                                ->dehydratedWhenHidden()
                                                ->hidden(fn (Get $get) => $get('unlimited_disk'))
                                                ->label('Disk Space Limit')
                                                ->inlineLabel()
                                                ->suffix(config('panel.use_binary_prefix') ? 'MiB' : 'MB')
                                                ->required()
                                                ->columnSpan(2)
                                                ->numeric()
                                                ->minValue(0),
                                        ]),
                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('unlimited_cpu')
                                                ->label('CPU')
                                                ->inlineLabel()
                                                ->inline()
                                                ->afterStateUpdated(fn (Set $set) => $set('cpu', 0))
                                                ->formatStateUsing(fn (Get $get) => $get('cpu') == 0)
                                                ->live()
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
                                                ->label('CPU Limit')
                                                ->inlineLabel()
                                                ->suffix('%')
                                                ->required()
                                                ->columnSpan(2)
                                                ->numeric()
                                                ->minValue(0),
                                        ]),
                                ]),
                            Fieldset::make('Advanced Limits')
                                ->columns(['default' => 1, 'sm' => 2, 'md' => 3, 'lg' => 3])
                                ->schema([
                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('swap_support')
                                                ->live()
                                                ->label('Swap Memory')
                                                ->inlineLabel()
                                                ->inline()
                                                ->columnSpan(2)
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    $value = match ($state) {
                                                        'unlimited' => -1,
                                                        'disabled' => 0,
                                                        'limited' => 128,
                                                        default => throw new LogicException('Invalid state')
                                                    };

                                                    $set('swap', $value);
                                                })
                                                ->formatStateUsing(function (Get $get) {
                                                    return match (true) {
                                                        $get('swap') > 0 => 'limited',
                                                        $get('swap') == 0 => 'disabled',
                                                        $get('swap') < 0 => 'unlimited',
                                                        default => throw new LogicException('Invalid state')
                                                    };
                                                })
                                                ->options([
                                                    'unlimited' => 'Unlimited',
                                                    'limited' => 'Limited',
                                                    'disabled' => 'Disabled',
                                                ])
                                                ->colors([
                                                    'unlimited' => 'primary',
                                                    'limited' => 'warning',
                                                    'disabled' => 'danger',
                                                ]),

                                            TextInput::make('swap')
                                                ->dehydratedWhenHidden()
                                                ->hidden(fn (Get $get) => match ($get('swap_support')) {
                                                    'disabled', 'unlimited', true => true,
                                                    default => false,
                                                })
                                                ->label('Swap Memory')
                                                ->inlineLabel()
                                                ->suffix(config('panel.use_binary_prefix') ? 'MiB' : 'MB')
                                                ->minValue(-1)
                                                ->columnSpan(2)
                                                ->required()
                                                ->integer(),
                                        ]),
                                    Hidden::make('io')
                                        ->helperText('The IO performance relative to other running containers')
                                        ->label('Block IO Proportion'),
                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('cpu_pinning')
                                                ->label('CPU Pinning')
                                                ->inlineLabel()
                                                ->inline()
                                                ->default(false)
                                                ->afterStateUpdated(fn (Set $set) => $set('threads', []))
                                                ->formatStateUsing(fn (Get $get) => !empty($get('threads')))
                                                ->live()
                                                ->options([
                                                    false => 'Disabled',
                                                    true => 'Enabled',
                                                ])
                                                ->colors([
                                                    false => 'success',
                                                    true => 'warning',
                                                ])
                                                ->columnSpan(2),

                                            TagsInput::make('threads')
                                                ->dehydratedWhenHidden()
                                                ->hidden(fn (Get $get) => !$get('cpu_pinning'))
                                                ->label('Pinned Threads')
                                                ->inlineLabel()
                                                ->required(fn (Get $get) => $get('cpu_pinning'))
                                                ->columnSpan(2)
                                                ->separator()
                                                ->splitKeys([','])
                                                ->placeholder('Add pinned thread, e.g. 0 or 2-4'),
                                        ]),
                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('oom_killer')
                                                ->label('OOM Killer')
                                                ->inlineLabel()
                                                ->inline()
                                                ->columnSpan(2)
                                                ->options([
                                                    false => 'Disabled',
                                                    true => 'Enabled',
                                                ])
                                                ->colors([
                                                    false => 'success',
                                                    true => 'danger',
                                                ]),
                                        ]),
                                ]),
                            Fieldset::make('Feature Limits')
                                ->inlineLabel()
                                ->columns(['default' => 1, 'sm' => 2, 'md' => 3, 'lg' => 3])
                                ->schema([
                                    TextInput::make('allocation_limit')
                                        ->suffixIcon('tabler-network')
                                        ->required()
                                        ->minValue(0)
                                        ->numeric(),
                                    TextInput::make('database_limit')
                                        ->suffixIcon('tabler-database')
                                        ->required()
                                        ->minValue(0)
                                        ->numeric(),
                                    TextInput::make('backup_limit')
                                        ->suffixIcon('tabler-copy-check')
                                        ->required()
                                        ->minValue(0)
                                        ->numeric(),
                                ]),
                            Fieldset::make('Docker Settings')
                                ->columns(['default' => 1, 'sm' => 2, 'md' => 3, 'lg' => 3])
                                ->schema([
                                    Select::make('select_image')
                                        ->label('Image Name')
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set, $state) => $set('image', $state))
                                        ->options(function ($state, Get $get, Set $set) {
                                            $egg = Egg::query()->find($get('egg_id'));
                                            $images = $egg->docker_images ?? [];

                                            $currentImage = $get('image');
                                            if (!$currentImage && $images) {
                                                $defaultImage = collect($images)->first();
                                                $set('image', $defaultImage);
                                                $set('select_image', $defaultImage);
                                            }

                                            return array_flip($images) + ['ghcr.io/custom-image' => 'Custom Image'];
                                        })
                                        ->selectablePlaceholder(false)
                                        ->columnSpan(1),

                                    TextInput::make('image')
                                        ->label('Image')
                                        ->required()
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $egg = Egg::query()->find($get('egg_id'));
                                            $images = $egg->docker_images ?? [];

                                            if (in_array($state, $images)) {
                                                $set('select_image', $state);
                                            } else {
                                                $set('select_image', 'ghcr.io/custom-image');
                                            }
                                        })
                                        ->placeholder('Enter a custom Image')
                                        ->columnSpan(2),

                                    KeyValue::make('docker_labels')
                                        ->label('Container Labels')
                                        ->keyLabel('Label Name')
                                        ->valueLabel('Label Description')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Egg')
                        ->icon('tabler-egg')
                        ->columns(['default' => 1, 'sm' => 3, 'md' => 3, 'lg' => 5])
                        ->schema([
                            Select::make('egg_id')
                                ->disabled()
                                ->prefixIcon('tabler-egg')
                                ->columnSpan(['default' => 6, 'sm' => 3, 'md' => 3, 'lg' => 4])
                                ->relationship('egg', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->hintAction(
                                    FormsAction::make('change_egg')
                                        ->hidden(fn ($operation) => $operation === 'view')
                                        ->action(function (array $data, Server $server, EggChangerService $service, $livewire) {
                                            /** @var Pages\EditServer|Pages\ViewServer $recordPage */
                                            $recordPage = $livewire;

                                            $service->handle($server, $data['egg_id'], $data['keepOldVariables']);

                                            // Use redirect instead of fillForm to prevent server variables from duplicating
                                            $recordPage->redirect($recordPage->getUrl(['record' => $server, 'tab' => '-egg-tab']), true);
                                        })
                                        ->form(fn (Server $server) => [
                                            Select::make('egg_id')
                                                ->label('New Egg')
                                                ->prefixIcon('tabler-egg')
                                                ->options(fn () => Egg::all()->filter(fn (Egg $egg) => $egg->id !== $server->egg->id)->mapWithKeys(fn (Egg $egg) => [$egg->id => $egg->name]))
                                                ->searchable()
                                                ->preload()
                                                ->required(),
                                            Toggle::make('keepOldVariables')
                                                ->label('Keep old variables if possible?')
                                                ->default(true),
                                        ])
                                ),
                            ToggleButtons::make('skip_scripts')
                                ->label('Run Egg Install Script?')
                                ->inline()
                                ->columnSpan(['default' => 6, 'sm' => 1, 'md' => 1, 'lg' => 2])
                                ->options([
                                    false => 'Yes',
                                    true => 'Skip',
                                ])
                                ->colors([
                                    false => 'primary',
                                    true => 'danger',
                                ])
                                ->icons([
                                    false => 'tabler-code',
                                    true => 'tabler-code-off',
                                ])
                                ->required(),
                            Textarea::make('startup')
                                ->label('Startup Command')
                                ->required()
                                ->columnSpan(6)
                                ->autosize(),
                            Textarea::make('defaultStartup')
                                ->hintAction(CopyAction::make())
                                ->label('Default Startup Command')
                                ->disabled()
                                ->autosize()
                                ->columnSpan(6)
                                ->formatStateUsing(function ($state, Get $get) {
                                    $egg = Egg::query()->find($get('egg_id'));

                                    return $egg->startup;
                                }),
                            Repeater::make('server_variables')
                                ->relationship('serverVariables', function (Builder $query, $livewire) {
                                    /** @var Pages\EditServer|Pages\ViewServer $recordPage */
                                    $recordPage = $livewire;

                                    /** @var Server $server */
                                    $server = $recordPage->getRecord();

                                    foreach ($server->variables as $variable) {
                                        ServerVariable::query()->firstOrCreate([
                                            'server_id' => $server->id,
                                            'variable_id' => $variable->id,
                                        ], [
                                            'variable_value' => $variable->server_value ?? '',
                                        ]);
                                    }

                                    return $query;
                                })
                                ->grid()
                                ->mutateRelationshipDataBeforeSaveUsing(function (array &$data): array {
                                    foreach ($data as $key => $value) {
                                        if (!isset($data['variable_value'])) {
                                            $data['variable_value'] = '';
                                        }
                                    }

                                    return $data;
                                })
                                ->reorderable(false)->addable(false)->deletable(false)
                                ->schema(function () {
                                    $text = TextInput::make('variable_value')
                                        ->hidden(fn (ServerVariable $serverVariable, Component $component) => self::shouldHideComponent($serverVariable->variable->rules, $component))
                                        ->required(fn (ServerVariable $serverVariable) => $serverVariable->variable->getRequiredAttribute())
                                        ->rules([
                                            fn (ServerVariable $serverVariable): Closure => function (string $attribute, $value, Closure $fail) use ($serverVariable) {
                                                $validator = Validator::make(['validatorkey' => $value], [
                                                    'validatorkey' => $serverVariable->variable->rules,
                                                ]);

                                                if ($validator->fails()) {
                                                    $message = str($validator->errors()->first())->replace('validatorkey', $serverVariable->variable->name);

                                                    $fail($message);
                                                }
                                            },
                                        ]);

                                    $select = Select::make('variable_value')
                                        ->hidden(fn (ServerVariable $serverVariable, Component $component) => self::shouldHideComponent($serverVariable->variable->rules, $component))
                                        ->options(fn (ServerVariable $serverVariable) => self::getSelectOptionsFromRules($serverVariable->variable->rules))
                                        ->selectablePlaceholder(false);

                                    $components = [$text, $select];

                                    foreach ($components as &$component) {
                                        $component = $component
                                            ->live(onBlur: true)
                                            ->hintIcon('tabler-code')
                                            ->label(fn (ServerVariable $serverVariable) => $serverVariable->variable->name)
                                            ->hintIconTooltip(fn (ServerVariable $serverVariable) => implode('|', $serverVariable->variable->rules))
                                            ->prefix(fn (ServerVariable $serverVariable) => '{{' . $serverVariable->variable->env_variable . '}}')
                                            ->helperText(fn (ServerVariable $serverVariable) => empty($serverVariable->variable->description) ? '—' : $serverVariable->variable->description);
                                    }

                                    return $components;
                                })
                                ->columnSpan(6),
                        ]),
                    Tab::make('Mounts')
                        ->icon('tabler-layers-linked')
                        ->schema([
                            CheckboxList::make('mounts')
                                ->label('Mounts')
                                ->relationship('mounts')
                                ->options(fn (Server $server) => $server->node->mounts->filter(fn (Mount $mount) => $mount->eggs->contains($server->egg))->mapWithKeys(fn (Mount $mount) => [$mount->id => $mount->name]))
                                ->descriptions(fn (Server $server) => $server->node->mounts->mapWithKeys(fn (Mount $mount) => [$mount->id => "$mount->source -> $mount->target"]))
                                ->helperText(fn (Server $server) => $server->node->mounts->isEmpty() ? 'No Mounts exist for this Node' : '')
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Actions')
                        ->icon('tabler-settings')
                        ->hiddenOn('view')
                        ->schema([
                            Fieldset::make('Server Actions')
                                ->columns(['default' => 1, 'sm' => 2, 'md' => 2, 'lg' => 6])
                                ->schema([
                                    Grid::make()
                                        ->columnSpan(3)
                                        ->schema([
                                            Actions::make([
                                                FormsAction::make('toggleInstall')
                                                    ->label('Toggle Install Status')
                                                    ->disabled(fn (Server $server) => $server->isSuspended())
                                                    ->action(function (ToggleInstallService $service, Server $server, $livewire) {
                                                        /** @var Pages\EditServer|Pages\ViewServer $recordPage */
                                                        $recordPage = $livewire;

                                                        $service->handle($server);

                                                        $recordPage->refreshFormData(['status', 'docker']);
                                                    }),
                                            ])->fullWidth(),
                                            ToggleButtons::make('')
                                                ->hint('If you need to change the install status from uninstalled to installed, or vice versa, you may do so with this button.'),
                                        ]),
                                    Grid::make()
                                        ->columnSpan(3)
                                        ->schema([
                                            Actions::make([
                                                FormsAction::make('toggleSuspend')
                                                    ->label('Suspend')
                                                    ->color('warning')
                                                    ->hidden(fn (Server $server) => $server->isSuspended())
                                                    ->action(function (SuspensionService $suspensionService, Server $server, $livewire) {
                                                        /** @var Pages\EditServer|Pages\ViewServer $recordPage */
                                                        $recordPage = $livewire;

                                                        $suspensionService->toggle($server, 'suspend');
                                                        Notification::make()->success()->title('Server Suspended!')->send();

                                                        $recordPage->refreshFormData(['status', 'docker']);
                                                    }),
                                                FormsAction::make('toggleUnsuspend')
                                                    ->label('Unsuspend')
                                                    ->color('success')
                                                    ->hidden(fn (Server $server) => !$server->isSuspended())
                                                    ->action(function (SuspensionService $suspensionService, Server $server, $livewire) {
                                                        /** @var Pages\EditServer|Pages\ViewServer $recordPage */
                                                        $recordPage = $livewire;

                                                        $suspensionService->toggle($server, 'unsuspend');
                                                        Notification::make()->success()->title('Server Unsuspended!')->send();

                                                        $recordPage->refreshFormData(['status', 'docker']);
                                                    }),
                                            ])->fullWidth(),
                                            ToggleButtons::make('')
                                                ->hidden(fn (Server $server) => $server->isSuspended())
                                                ->hint('This will suspend the server, stop any running processes, and immediately block the user from being able to access their files or otherwise manage the server through the panel or API.'),
                                            ToggleButtons::make('')
                                                ->hidden(fn (Server $server) => !$server->isSuspended())
                                                ->hint('This will unsuspend the server and restore normal user access.'),
                                        ]),
                                    Grid::make()
                                        ->columnSpan(3)
                                        ->schema([
                                            Actions::make([
                                                FormsAction::make('transfer')
                                                    ->label('Transfer Soon™')
                                                    ->action(fn (TransferServerService $transfer, Server $server) => $transfer->handle($server, []))
                                                    ->disabled() //TODO!
                                                    ->form([ //TODO!
                                                        Select::make('newNode')
                                                            ->label('New Node')
                                                            ->required()
                                                            ->options([
                                                                true => 'on',
                                                                false => 'off',
                                                            ]),
                                                        Select::make('newMainAllocation')
                                                            ->label('New Main Allocation')
                                                            ->required()
                                                            ->options([
                                                                true => 'on',
                                                                false => 'off',
                                                            ]),
                                                        Select::make('newAdditionalAllocation')
                                                            ->label('New Additional Allocations')
                                                            ->options([
                                                                true => 'on',
                                                                false => 'off',
                                                            ]),
                                                    ])
                                                    ->modalHeading('Transfer'),
                                            ])->fullWidth(),
                                            ToggleButtons::make('')
                                                ->hint('Transfer this server to another node connected to this panel. Warning! This feature has not been fully tested and may have bugs.'),
                                        ]),
                                    Grid::make()
                                        ->columnSpan(3)
                                        ->schema([
                                            Actions::make([
                                                FormsAction::make('reinstall')
                                                    ->label('Reinstall')
                                                    ->color('danger')
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Are you sure you want to reinstall this server?')
                                                    ->modalDescription('!! This can result in unrecoverable data loss !!')
                                                    ->disabled(fn (Server $server) => $server->isSuspended())
                                                    ->action(fn (ReinstallServerService $service, Server $server) => $service->handle($server)),
                                            ])->fullWidth(),
                                            ToggleButtons::make('')
                                                ->hint('This will reinstall the server with the assigned egg install script.'),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function shouldHideComponent(array $rules, Component $component): bool
    {
        $containsRuleIn = array_first($rules, fn ($value) => str($value)->startsWith('in:'), false);

        if ($component instanceof Select) {
            return !$containsRuleIn;
        }

        if ($component instanceof TextInput) {
            return $containsRuleIn;
        }

        throw new Exception('Component type not supported: ' . $component::class);
    }

    public static function getSelectOptionsFromRules(array $rules): array
    {
        $inRule = array_first($rules, fn ($value) => str($value)->startsWith('in:'));

        return str($inRule)
            ->after('in:')
            ->explode(',')
            ->each(fn ($value) => str($value)->trim())
            ->mapWithKeys(fn ($value) => [$value => $value])
            ->all();
    }

    public static function getRelations(): array
    {
        return [
            AllocationsRelationManager::class,
            DatabasesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'view' => Pages\ViewServer::route('/{record}'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }
}

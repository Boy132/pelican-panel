<?php

namespace App\Filament\Admin\Resources\ServerResource\Pages;

use App\Filament\Admin\Resources\ServerResource;
use App\Models\Allocation;
use App\Models\Egg;
use App\Models\Node;
use App\Models\User;
use App\Services\Allocations\AssignmentService;
use App\Services\Servers\RandomWordService;
use App\Services\Servers\ServerCreationService;
use App\Services\Users\UserCreationService;
use Closure;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use LogicException;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;

    protected static bool $canCreateAnother = false;

    public ?Node $node = null;

    private ServerCreationService $serverCreationService;

    public function boot(ServerCreationService $serverCreationService): void
    {
        $this->serverCreationService = $serverCreationService;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Information')
                        ->label('Information')
                        ->icon('tabler-info-circle')
                        ->completedIcon('tabler-check')
                        ->columns([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 4,
                            'lg' => 6,
                        ])
                        ->schema([
                            TextInput::make('name')
                                ->prefixIcon('tabler-server')
                                ->label('Name')
                                ->suffixAction(Forms\Components\Actions\Action::make('random')
                                    ->icon('tabler-dice-' . random_int(1, 6))
                                    ->action(function (Set $set, Get $get) {
                                        $egg = Egg::find($get('egg_id'));
                                        $prefix = $egg ? str($egg->name)->lower()->kebab() . '-' : '';

                                        $word = (new RandomWordService())->word();

                                        $set('name', $prefix . $word);
                                    }))
                                ->columnSpan([
                                    'default' => 2,
                                    'sm' => 3,
                                    'md' => 2,
                                    'lg' => 3,
                                ])
                                ->required()
                                ->maxLength(255),

                            Select::make('owner_id')
                                ->preload()
                                ->prefixIcon('tabler-user')
                                ->default(auth()->user()->id)
                                ->label('Owner')
                                ->columnSpan([
                                    'default' => 2,
                                    'sm' => 3,
                                    'md' => 3,
                                    'lg' => 3,
                                ])
                                ->relationship('user', 'username')
                                ->searchable(['username', 'email'])
                                ->getOptionLabelFromRecordUsing(fn (User $user) => "$user->email | $user->username " . ($user->isRootAdmin() ? '(admin)' : ''))
                                ->createOptionForm([
                                    TextInput::make('username')
                                        ->alphaNum()
                                        ->required()
                                        ->minLength(3)
                                        ->maxLength(255),

                                    TextInput::make('email')
                                        ->email()
                                        ->required()
                                        ->unique()
                                        ->maxLength(255),

                                    TextInput::make('password')
                                        ->hintIcon('tabler-question-mark')
                                        ->hintIconTooltip('Providing a user password is optional. New user email will prompt users to create a password the first time they login.')
                                        ->password(),
                                ])
                                ->createOptionUsing(function ($data, UserCreationService $service) {
                                    $service->handle($data);

                                    $this->refreshForm();
                                })
                                ->required(),

                            Select::make('node_id')
                                ->disabledOn('edit')
                                ->prefixIcon('tabler-server-2')
                                ->default(fn () => ($this->node = Node::query()->latest()->first())?->id)
                                ->columnSpan([
                                    'default' => 2,
                                    'sm' => 3,
                                    'md' => 6,
                                    'lg' => 6,
                                ])
                                ->live()
                                ->relationship('node', 'name')
                                ->searchable()
                                ->preload()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $set('allocation_id', null);
                                    $this->node = Node::find($state);
                                })
                                ->required(),

                            Select::make('allocation_id')
                                ->preload()
                                ->live()
                                ->prefixIcon('tabler-network')
                                ->label('Primary Allocation')
                                ->columnSpan([
                                    'default' => 2,
                                    'sm' => 3,
                                    'md' => 2,
                                    'lg' => 3,
                                ])
                                ->disabled(fn (Get $get) => $get('node_id') === null)
                                ->searchable(['ip', 'port', 'ip_alias'])
                                ->afterStateUpdated(function (Set $set) {
                                    $set('allocation_additional', null);
                                    $set('allocation_additional.needstobeastringhere.extra_allocations', null);
                                })
                                ->getOptionLabelFromRecordUsing(
                                    fn (Allocation $allocation) => "$allocation->ip:$allocation->port" .
                                        ($allocation->ip_alias ? " ($allocation->ip_alias)" : '')
                                )
                                ->placeholder(function (Get $get) {
                                    $node = Node::find($get('node_id'));

                                    if ($node?->allocations) {
                                        return 'Select an Allocation';
                                    }

                                    return 'Create a New Allocation';
                                })
                                ->relationship(
                                    'allocation',
                                    'ip',
                                    fn (Builder $query, Get $get) => $query
                                        ->where('node_id', $get('node_id'))
                                        ->whereNull('server_id'),
                                )
                                ->createOptionForm(fn (Get $get) => [
                                    Select::make('allocation_ip')
                                        ->options(collect(Node::find($get('node_id'))?->ipAddresses())->mapWithKeys(fn (string $ip) => [$ip => $ip]))
                                        ->label('IP Address')
                                        ->inlineLabel()
                                        ->ipv4()
                                        ->helperText("Usually your machine's public IP unless you are port forwarding.")
                                        ->required(),
                                    TextInput::make('allocation_alias')
                                        ->label('Alias')
                                        ->inlineLabel()
                                        ->default(null)
                                        ->datalist([
                                            $get('name'),
                                            Egg::find($get('egg_id'))?->name,
                                        ])
                                        ->helperText('Optional display name to help you remember what these are.')
                                        ->required(false),
                                    TagsInput::make('allocation_ports')
                                        ->placeholder('Examples: 27015, 27017-27019')
                                        ->helperText(new HtmlString('
                                These are the ports that users can connect to this Server through.
                                <br />
                                You would have to port forward these on your home network.
                            '))
                                        ->label('Ports')
                                        ->inlineLabel()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $ports = collect();
                                            $update = false;
                                            foreach ($state as $portEntry) {
                                                if (!str_contains($portEntry, '-')) {
                                                    if (is_numeric($portEntry)) {
                                                        $ports->push((int) $portEntry);

                                                        continue;
                                                    }

                                                    // Do not add non-numerical ports
                                                    $update = true;

                                                    continue;
                                                }

                                                $update = true;
                                                [$start, $end] = explode('-', $portEntry);
                                                if (!is_numeric($start) || !is_numeric($end)) {
                                                    continue;
                                                }

                                                $start = max((int) $start, 0);
                                                $end = min((int) $end, 2 ** 16 - 1);
                                                $range = $start <= $end ? range($start, $end) : range($end, $start);
                                                foreach ($range as $i) {
                                                    if ($i > 1024 && $i <= 65535) {
                                                        $ports->push($i);
                                                    }
                                                }
                                            }

                                            $uniquePorts = $ports->unique()->values();
                                            if ($ports->count() > $uniquePorts->count()) {
                                                $update = true;
                                                $ports = $uniquePorts;
                                            }

                                            $sortedPorts = $ports->sort()->values();
                                            if ($sortedPorts->all() !== $ports->all()) {
                                                $update = true;
                                                $ports = $sortedPorts;
                                            }

                                            if ($update) {
                                                $set('allocation_ports', $ports->all());
                                            }
                                        })
                                        ->splitKeys(['Tab', ' ', ','])
                                        ->required(),
                                ])
                                ->createOptionUsing(function (array $data, Get $get, AssignmentService $assignmentService): int {
                                    return collect(
                                        $assignmentService->handle(Node::find($get('node_id')), $data)
                                    )->first();
                                })
                                ->required(),

                            Repeater::make('allocation_additional')
                                ->label('Additional Allocations')
                                ->columnSpan([
                                    'default' => 2,
                                    'sm' => 3,
                                    'md' => 3,
                                    'lg' => 3,
                                ])
                                ->addActionLabel('Add Allocation')
                                ->disabled(fn (Get $get) => $get('allocation_id') === null)
                                // ->addable() TODO disable when all allocations are taken
                                // ->addable() TODO disable until first additional allocation is selected
                                ->simple(
                                    Select::make('extra_allocations')
                                        ->live()
                                        ->preload()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->prefixIcon('tabler-network')
                                        ->label('Additional Allocations')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get) => $get('../../node_id') === null)
                                        ->searchable(['ip', 'port', 'ip_alias'])
                                        ->getOptionLabelFromRecordUsing(
                                            fn (Allocation $allocation) => "$allocation->ip:$allocation->port" .
                                                ($allocation->ip_alias ? " ($allocation->ip_alias)" : '')
                                        )
                                        ->placeholder('Select additional Allocations')
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->relationship(
                                            'allocations',
                                            'ip',
                                            fn (Builder $query, Get $get, Select $component, $state) => $query
                                                ->where('node_id', $get('../../node_id'))
                                                ->whereNot('id', $get('../../allocation_id'))
                                                ->whereNull('server_id'),
                                        ),
                                ),

                            Textarea::make('description')
                                ->placeholder('Description')
                                ->rows(3)
                                ->columnSpan([
                                    'default' => 2,
                                    'sm' => 6,
                                    'md' => 6,
                                    'lg' => 6,
                                ])
                                ->label('Description'),
                        ]),

                    Step::make('Egg Configuration')
                        ->label('Egg Configuration')
                        ->icon('tabler-egg')
                        ->completedIcon('tabler-check')
                        ->columns([
                            'default' => 1,
                            'sm' => 4,
                            'md' => 4,
                            'lg' => 6,
                        ])
                        ->schema([
                            Select::make('egg_id')
                                ->prefixIcon('tabler-egg')
                                ->relationship('egg', 'name')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 2,
                                    'md' => 2,
                                    'lg' => 4,
                                ])
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set, Get $get, $old) {
                                    $egg = Egg::query()->find($state);
                                    $set('startup', $egg->startup ?? '');
                                    $set('image', '');

                                    $variables = $egg->variables ?? [];
                                    $serverVariables = collect();
                                    foreach ($variables as $variable) {
                                        $serverVariables->add($variable->toArray());
                                    }

                                    $variables = [];
                                    $set($path = 'server_variables', $serverVariables->sortBy(['sort'])->all());
                                    for ($i = 0; $i < $serverVariables->count(); $i++) {
                                        $set("$path.$i.variable_value", $serverVariables[$i]['default_value']);
                                        $set("$path.$i.variable_id", $serverVariables[$i]['id']);
                                        $variables[$serverVariables[$i]['env_variable']] = $serverVariables[$i]['default_value'];
                                    }

                                    $set('environment', $variables);

                                    $previousEgg = Egg::query()->find($old);
                                    if (!$get('name') || $previousEgg?->getKebabName() === $get('name')) {
                                        $set('name', $egg->getKebabName());
                                    }
                                })
                                ->required(),

                            ToggleButtons::make('skip_scripts')
                                ->label('Run Egg Install Script?')
                                ->default(false)
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                    'lg' => 1,
                                ])
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
                                ->inline()
                                ->required(),

                            ToggleButtons::make('start_on_completion')
                                ->label('Start Server After Install?')
                                ->default(true)
                                ->required()
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                    'lg' => 1,
                                ])
                                ->options([
                                    true => 'Yes',
                                    false => 'No',
                                ])
                                ->colors([
                                    true => 'primary',
                                    false => 'danger',
                                ])
                                ->icons([
                                    true => 'tabler-code',
                                    false => 'tabler-code-off',
                                ])
                                ->inline(),

                            Textarea::make('startup')
                                ->hintIcon('tabler-code')
                                ->label('Startup Command')
                                ->hidden(fn (Get $get) => $get('egg_id') === null)
                                ->required()
                                ->live()
                                ->rows(function ($state) {
                                    return str($state)->explode("\n")->reduce(
                                        fn (int $carry, $line) => $carry + floor(strlen($line) / 125),
                                        1
                                    );
                                })
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 4,
                                    'md' => 4,
                                    'lg' => 6,
                                ]),

                            Hidden::make('environment')->default([]),

                            Section::make('Variables')
                                ->icon('tabler-eggs')
                                ->iconColor('primary')
                                ->hidden(fn (Get $get) => $get('egg_id') === null)
                                ->collapsible()
                                ->columnSpanFull()
                                ->schema([
                                    Placeholder::make('Select an egg first to show its variables!')
                                        ->hidden(fn (Get $get) => $get('egg_id')),

                                    Placeholder::make('The selected egg has no variables!')
                                        ->hidden(fn (Get $get) => !$get('egg_id') ||
                                            Egg::query()->find($get('egg_id'))?->variables()?->count()
                                        ),

                                    Repeater::make('server_variables')
                                        ->label('')
                                        ->relationship('serverVariables')
                                        ->saveRelationshipsBeforeChildrenUsing(null)
                                        ->saveRelationshipsUsing(null)
                                        ->grid(2)
                                        ->reorderable(false)
                                        ->addable(false)
                                        ->deletable(false)
                                        ->default([])
                                        ->hidden(fn ($state) => empty($state))
                                        ->schema(function () {

                                            $text = TextInput::make('variable_value')
                                                ->hidden($this->shouldHideComponent(...))
                                                ->required(fn (Get $get) => in_array('required', $get('rules')))
                                                ->rules(
                                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                        $validator = Validator::make(['validatorkey' => $value], [
                                                            'validatorkey' => $get('rules'),
                                                        ]);

                                                        if ($validator->fails()) {
                                                            $message = str($validator->errors()->first())->replace('validatorkey', $get('name'))->toString();

                                                            $fail($message);
                                                        }
                                                    },
                                                );

                                            $select = Select::make('variable_value')
                                                ->hidden($this->shouldHideComponent(...))
                                                ->options($this->getSelectOptionsFromRules(...))
                                                ->selectablePlaceholder(false);

                                            $components = [$text, $select];

                                            foreach ($components as &$component) {
                                                $component = $component
                                                    ->live(onBlur: true)
                                                    ->hintIcon('tabler-code')
                                                    ->label(fn (Get $get) => $get('name'))
                                                    ->hintIconTooltip(fn (Get $get) => implode('|', $get('rules')))
                                                    ->prefix(fn (Get $get) => '{{' . $get('env_variable') . '}}')
                                                    ->helperText(fn (Get $get) => empty($get('description')) ? '—' : $get('description'))
                                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                        $environment = $get($envPath = '../../environment');
                                                        $environment[$get('env_variable')] = $state;
                                                        $set($envPath, $environment);
                                                    });
                                            }

                                            return $components;
                                        })
                                        ->columnSpan(2),
                                ]),
                        ]),
                    Step::make('Environment Configuration')
                        ->label('Environment Configuration')
                        ->icon('tabler-brand-docker')
                        ->completedIcon('tabler-check')
                        ->schema([
                            Fieldset::make('Resource Limits')
                                ->columnSpan(6)
                                ->columns([
                                    'default' => 1,
                                    'sm' => 2,
                                    'md' => 3,
                                    'lg' => 3,
                                ])
                                ->schema([
                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('unlimited_cpu')
                                                ->label('CPU')->inlineLabel()->inline()
                                                ->default(true)
                                                ->afterStateUpdated(fn (Set $set) => $set('cpu', 0))
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
                                                ->label('CPU Limit')->inlineLabel()
                                                ->suffix('%')
                                                ->default(0)
                                                ->required()
                                                ->columnSpan(2)
                                                ->numeric()
                                                ->minValue(0)
                                                ->helperText('100% equals one CPU core.'),
                                        ]),
                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('unlimited_mem')
                                                ->label('Memory')->inlineLabel()->inline()
                                                ->default(true)
                                                ->afterStateUpdated(fn (Set $set) => $set('memory', 0))
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
                                                ->label('Memory Limit')->inlineLabel()
                                                ->suffix(config('panel.use_binary_prefix') ? 'MiB' : 'MB')
                                                ->default(0)
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
                                                ->label('Disk Space')->inlineLabel()->inline()
                                                ->default(true)
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set) => $set('disk', 0))
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
                                                ->label('Disk Space Limit')->inlineLabel()
                                                ->suffix(config('panel.use_binary_prefix') ? 'MiB' : 'MB')
                                                ->default(0)
                                                ->required()
                                                ->columnSpan(2)
                                                ->numeric()
                                                ->minValue(0),
                                        ]),

                                ]),

                            Fieldset::make('Advanced Limits')
                                ->columnSpan(6)
                                ->columns([
                                    'default' => 1,
                                    'sm' => 2,
                                    'md' => 3,
                                    'lg' => 3,
                                ])
                                ->schema([
                                    Hidden::make('io')
                                        ->helperText('The IO performance relative to other running containers')
                                        ->label('Block IO Proportion')
                                        ->default(500),

                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('cpu_pinning')
                                                ->label('CPU Pinning')->inlineLabel()->inline()
                                                ->default(false)
                                                ->afterStateUpdated(fn (Set $set) => $set('threads', []))
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
                                                ->label('Pinned Threads')->inlineLabel()
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
                                            ToggleButtons::make('swap_support')
                                                ->live()
                                                ->label('Swap Memory')
                                                ->inlineLabel()
                                                ->inline()
                                                ->columnSpan(2)
                                                ->default('disabled')
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    $value = match ($state) {
                                                        'unlimited' => -1,
                                                        'disabled' => 0,
                                                        'limited' => 128,
                                                        default => throw new LogicException('Invalid state'),
                                                    };

                                                    $set('swap', $value);
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
                                                    'disabled', 'unlimited' => true,
                                                    default => false,
                                                })
                                                ->label('Swap Memory')
                                                ->default(0)
                                                ->suffix(config('panel.use_binary_prefix') ? 'MiB' : 'MB')
                                                ->minValue(-1)
                                                ->columnSpan(2)
                                                ->inlineLabel()
                                                ->required()
                                                ->integer(),
                                        ]),

                                    Grid::make()
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->schema([
                                            ToggleButtons::make('oom_killer')
                                                ->label('OOM Killer')
                                                ->inlineLabel()->inline()
                                                ->default(false)
                                                ->columnSpan(2)
                                                ->options([
                                                    false => 'Disabled',
                                                    true => 'Enabled',
                                                ])
                                                ->colors([
                                                    false => 'success',
                                                    true => 'danger',
                                                ]),

                                            TextInput::make('oom_disabled_hidden')
                                                ->hidden(),
                                        ]),
                                ]),

                            Fieldset::make('Feature Limits')
                                ->inlineLabel()
                                ->columnSpan(6)
                                ->columns([
                                    'default' => 1,
                                    'sm' => 2,
                                    'md' => 3,
                                    'lg' => 3,
                                ])
                                ->schema([
                                    TextInput::make('allocation_limit')
                                        ->label('Allocations')
                                        ->suffixIcon('tabler-network')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0),
                                    TextInput::make('database_limit')
                                        ->label('Databases')
                                        ->suffixIcon('tabler-database')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0),
                                    TextInput::make('backup_limit')
                                        ->label('Backups')
                                        ->suffixIcon('tabler-copy-check')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0),
                                ]),
                            Fieldset::make('Docker Settings')
                                ->columns([
                                    'default' => 1,
                                    'sm' => 2,
                                    'md' => 3,
                                    'lg' => 4,
                                ])
                                ->columnSpan(6)
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
                                        ->columnSpan([
                                            'default' => 1,
                                            'sm' => 2,
                                            'md' => 3,
                                            'lg' => 2,
                                        ]),

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
                                        ->columnSpan([
                                            'default' => 1,
                                            'sm' => 2,
                                            'md' => 3,
                                            'lg' => 2,
                                        ]),

                                    KeyValue::make('docker_labels')
                                        ->label('Container Labels')
                                        ->keyLabel('Title')
                                        ->valueLabel('Description')
                                        ->columnSpanFull(),

                                    CheckboxList::make('mounts')
                                        ->live()
                                        ->relationship('mounts')
                                        ->options(fn () => $this->node?->mounts->mapWithKeys(fn ($mount) => [$mount->id => $mount->name]) ?? [])
                                        ->descriptions(fn () => $this->node?->mounts->mapWithKeys(fn ($mount) => [$mount->id => "$mount->source -> $mount->target"]) ?? [])
                                        ->label('Mounts')
                                        ->helperText(fn () => $this->node?->mounts->isNotEmpty() ? '' : 'No Mounts exist for this Node')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                    ->columnSpanFull()
                    ->nextAction(fn (Action $action) => $action->label('Next Step'))
                    ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                                        <x-filament::button
                                                type="submit"
                                                size="sm"
                                            >
                                                Create Server
                                            </x-filament::button>
                                        BLADE))),
            ]);
    }

    public function refreshForm(): void
    {
        $this->fillForm();
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['allocation_additional'] = collect($data['allocation_additional'])->filter()->all();

        return $this->serverCreationService->handle($data);
    }

    private function shouldHideComponent(Get $get, Component $component): bool
    {
        $containsRuleIn = collect($get('rules'))->reduce(
            fn ($result, $value) => $result === true && !str($value)->startsWith('in:'), true
        );

        if ($component instanceof Select) {
            return $containsRuleIn;
        }

        if ($component instanceof TextInput) {
            return !$containsRuleIn;
        }

        throw new Exception('Component type not supported: ' . $component::class);
    }

    private function getSelectOptionsFromRules(Get $get): array
    {
        $inRule = collect($get('rules'))->reduce(
            fn ($result, $value) => str($value)->startsWith('in:') ? $value : $result, ''
        );

        return str($inRule)
            ->after('in:')
            ->explode(',')
            ->each(fn ($value) => str($value)->trim())
            ->mapWithKeys(fn ($value) => [$value => $value])
            ->all();
    }
}
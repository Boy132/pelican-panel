<?php

namespace App\Filament\Admin\Resources;

use AbdelhamidErrahmouni\FilamentMonacoEditor\MonacoEditor;
use App\Filament\Admin\Resources\EggResource\Pages;
use App\Filament\Admin\RelationManagers\ServersRelationManager;
use App\Filament\Components\Tables\Actions\ImportEggAction;
use App\Models\Egg;
use App\Filament\Components\Tables\Actions\ExportEggAction;
use App\Filament\Components\Tables\Actions\UpdateEggAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class EggResource extends Resource
{
    protected static ?string $model = Egg::class;

    protected static ?string $navigationIcon = 'tabler-eggs';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'tags', 'uuid', 'id'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->defaultPaginationPageOption(25)
            ->checkIfRecordIsSelectableUsing(fn (Egg $egg) => $egg->servers_count <= 0)
            ->columns([
                TextColumn::make('name')
                    ->icon('tabler-egg')
                    ->description(fn (Egg $record) => (strlen($record->description) > 120) ? substr($record->description, 0, 120).'...' : $record->description)
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('servers_count')
                    ->label('Servers')
                    ->counts('servers')
                    ->icon('tabler-server'),
            ])
            ->actions([
                ViewAction::make()
                    ->hidden(fn (Egg $egg) => self::canEdit($egg)),
                EditAction::make(),
                ExportEggAction::make(),
                UpdateEggAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Egg $egg) => $egg->servers_count > 0),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn () => auth()->user()->can('delete egg')),
                ]),
            ])
            ->emptyStateIcon(self::getNavigationIcon())
            ->emptyStateDescription('')
            ->emptyStateHeading('No Eggs')
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('filament-panels::resources/pages/create-record.title', ['label' => self::getTitleCaseModelLabel()])),
                ImportEggAction::make(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()->tabs([
                    Tab::make('Configuration')
                        ->columns(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2])
                        ->icon('tabler-egg')
                        ->schema(self::configurationTab()),
                    Tab::make('Process Management')
                        ->columns()
                        ->icon('tabler-server-cog')
                        ->schema(self::processManagementTab()),
                    Tab::make('Egg Variables')
                        ->columnSpanFull()
                        ->icon('tabler-variable')
                        ->schema(self::eggVariablesTab()),
                    Tab::make('Install Script')
                        ->columns(3)
                        ->icon('tabler-file-download')
                        ->schema(self::installScriptTab()),
                ])->columnSpanFull()->persistTabInQueryString(),
            ]);
    }

    private static function configurationTab(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->helperText('A simple, human-readable name to use as an identifier for this Egg.'),
            TextInput::make('author')
                ->required()
                ->maxLength(255)
                ->email()
                ->disabledOn('edit')
                ->helperText('The author of this version of the Egg. Uploading a new Egg configuration from a different author will change this.'),
            TextInput::make('uuid')
                ->label('Egg UUID')
                ->disabled()
                ->hiddenOn('create')
                ->helperText('This is the globally unique identifier for this Egg which Wings uses as an identifier.'),
            TextInput::make('id')
                ->label('Egg ID')
                ->disabled()
                ->hiddenOn('create'),
            Textarea::make('description')
                ->rows(3)
                ->columnSpanFull()
                ->helperText('A description of this Egg that will be displayed throughout the Panel as needed.'),
            Textarea::make('startup')
                ->rows(2)
                ->columnSpanFull()
                ->required()
                ->placeholder('java -Xms128M -XX:MaxRAMPercentage=95.0 -jar {{SERVER_JARFILE}}')
                ->helperText('The default startup command that should be used for new servers using this Egg.'),
            TextInput::make('update_url')
                ->label('Update URL')
                ->url()
                ->columnSpanFull()
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip('URLs must point directly to the raw .json file.'),
            TagsInput::make('features')
                ->placeholder('Add Feature'),
            TagsInput::make('tags')
                ->placeholder('Add Tags'),
            TagsInput::make('file_denylist')
                ->placeholder('denied-file.txt')
                ->helperText('A list of files that the end user is not allowed to edit.'),
            Toggle::make('force_outgoing_ip')
                ->inline(false)
                ->hintIcon('tabler-question-mark')
                ->hintIconTooltip("Forces all outgoing network traffic to have its Source IP NATed to the IP of the server's primary allocation IP.
                                    Required for certain games to work properly when the Node has multiple public IP addresses.
                                    Enabling this option will disable internal networking for any servers using this egg, causing them to be unable to internally access other servers on the same node."),
            KeyValue::make('docker_images')
                ->required()
                ->live()
                ->columnSpanFull()
                ->addActionLabel('Add Image')
                ->keyLabel('Name')
                ->valueLabel('Image URI')
                ->helperText('The docker images available to servers using this egg.'),
        ];
    }

    private static function processManagementTab(): array
    {
        return [
            Select::make('config_from')
                ->label('Copy Settings From')
                ->relationship('configFrom', 'name', ignoreRecord: true)
                ->hiddenOn('create')
                ->placeholder('None')
                ->helperText('If you would like to default to settings from another Egg select it from the menu above.'),
            TextInput::make('config_stop')
                ->maxLength(255)
                ->label('Stop Command')
                ->helperText('The command that should be sent to server processes to stop them gracefully. If you need to send a SIGINT you should enter ^C here.'),
            Textarea::make('config_startup')
                ->label('Start Configuration')
                ->helperText('List of values the daemon should be looking for when booting a server to determine completion.')
                ->rows(10)
                ->json(),
            Textarea::make('config_files')
                ->label('Configuration Files')
                ->helperText('This should be a JSON representation of configuration files to modify and what parts should be changed.')
                ->rows(10)
                ->json(),
            Textarea::make('config_logs')
                ->label('Log Configuration')
                ->helperText('This should be a JSON representation of where log files are stored, and whether or not the daemon should be creating custom logs.')
                ->rows(10)
                ->json(),
        ];
    }

    private static function eggVariablesTab(): array
    {
        return [
            Repeater::make('variables')
                ->label('')
                ->grid()
                ->relationship('variables')
                ->name('name')
                ->reorderable()
                ->collapsible()
                ->collapsed()
                ->orderColumn()
                ->defaultItems(0)
                ->addActionLabel('New Variable')
                ->itemLabel(fn (array $state) => $state['name'])
                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                    $data['default_value'] ??= '';
                    $data['description'] ??= '';
                    $data['rules'] ??= [];
                    $data['user_viewable'] ??= '';
                    $data['user_editable'] ??= '';

                    return $data;
                })
                ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                    $data['default_value'] ??= '';
                    $data['description'] ??= '';
                    $data['rules'] ??= [];
                    $data['user_viewable'] ??= '';
                    $data['user_editable'] ??= '';

                    return $data;
                })
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(debounce: 750)
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->afterStateUpdated(fn (Set $set, $state) => $set('env_variable', str($state)->trim()->snake()->upper()->toString())),
                    Textarea::make('description')
                        ->columnSpanFull(),
                    TextInput::make('env_variable')
                        ->label('Environment Variable')
                        ->maxLength(255)
                        ->prefix('{{')
                        ->suffix('}}')
                        ->hintIcon('tabler-code')
                        ->hintIconTooltip(fn ($state) => "{{{$state}}}")
                        ->required(),
                    TextInput::make('default_value')
                        ->maxLength(255),
                    Fieldset::make('User Permissions')
                        ->schema([
                            Checkbox::make('user_viewable')
                                ->label('Viewable'),
                            Checkbox::make('user_editable')
                                ->label('Editable'),
                        ]),
                    TagsInput::make('rules')
                        ->columnSpanFull()
                        ->placeholder('Add Rule')
                        ->reorderable()
                        ->suggestions([
                            'required',
                            'nullable',
                            'string',
                            'integer',
                            'numeric',
                            'boolean',
                            'alpha',
                            'alpha_dash',
                            'alpha_num',
                            'url',
                            'email',
                            'regex:',
                            'min:',
                            'max:',
                            'between:',
                            'between:1024,65535',
                            'in:',
                            'in:true,false',
                        ]),
                ]),
        ];
    }

    private static function installScriptTab(): array
    {
        return [
            Select::make('copy_script_from')
                ->relationship('scriptFrom', 'name', ignoreRecord: true)
                ->hiddenOn('create')
                ->placeholder('None'),
            TextInput::make('script_container')
                ->required()
                ->maxLength(255)
                ->default('alpine:3.4'),
            Select::make('script_entry')
                ->required()
                ->selectablePlaceholder(false)
                ->default('bash')
                ->options(['bash', 'ash', '/bin/bash']),
            MonacoEditor::make('script_install')
                ->label('Install Script')
                ->placeholderText('')
                ->columnSpanFull()
                ->fontSize('16px')
                ->language('shell')
                ->view('filament.plugins.monaco-editor'),
        ];
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
            'index' => Pages\ListEggs::route('/'),
            'create' => Pages\CreateEgg::route('/create'),
            'view' => Pages\ViewEgg::route('/{record}'),
            'edit' => Pages\EditEgg::route('/{record}/edit'),
        ];
    }
}

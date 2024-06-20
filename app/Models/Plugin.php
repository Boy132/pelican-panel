<?php

namespace App\Models;

use App\Enums\PluginStatus;

/**
 * @property string $package
 * @property string $class
 * @property PluginStatus $status
 * @property string $name
 * @property string $description
 * @property string $author
 * @property string $panel
 * @property string $category
 */
class Plugin extends Model
{
    public const RESOURCE_NAME = 'plugin';

    protected $primaryKey = 'package';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'plugins';

    protected $fillable = [
        'package', 'class', 'name', 'description', 'author', 'panel', 'category',
    ];

    public static array $validationRules = [
        'package' => 'required|string',
        'class' => 'required|string',
        'status' => 'string',
        'name' => 'required|string',
        'description' => 'required|string',
        'author' => 'required|string',
        'panel' => 'required|string|in:admin,app,both',
        'category' => 'required|string|in:plugin,theme,language',
    ];

    protected $attributes = [
        'status' => PluginStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'status' => PluginStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'package';
    }

    public function shouldLoad(string $panelId): bool
    {
        return !$this->isDisabled() && ($this->panel === 'both' || $this->panel === $panelId);
    }

    public function isDisabled(): bool
    {
        return $this->status === PluginStatus::Disabled;
    }

    public function hasErrored(): bool
    {
        return $this->status === PluginStatus::Errored;
    }
}

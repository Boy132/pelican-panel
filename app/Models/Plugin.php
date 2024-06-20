<?php

namespace App\Models;

/**
 * @property string $package
 * @property string $class
 * @property string $name
 * @property string $panel
 * @property string $category
 * @property bool $enabled
 */
class Plugin extends Model
{
    public const RESOURCE_NAME = 'plugin';

    protected $primaryKey = 'package';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'plugins';

    protected $fillable = [
        'package', 'class', 'name', 'panel', 'category',
    ];

    public static array $validationRules = [
        'package' => 'required|string',
        'class' => 'required|string',
        'name' => 'required|string',
        'panel' => 'required|string|in:admin,app,both',
        'category' => 'required|string|in:plugin,theme,language',
        'enabled' => 'boolean',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'package';
    }

    public function shouldLoad(string $panelId): bool
    {
        return $this->enabled && ($this->panel === 'both' || $this->panel === $panelId);
    }
}

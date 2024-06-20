<?php

namespace App\Models;

/**
 * @property string $package
 * @property string $class
 * @property string $name
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
        'package', 'class', 'name', 'panel', 'category',
    ];

    public static array $validationRules = [
        'package' => 'required|string',
        'class' => 'required|string',
        'name' => 'required|string',
        'panel' => 'required|string|in:admin,app',
        'category' => 'required|string|in:plugin,theme,language',
    ];

    public function getRouteKeyName(): string
    {
        return 'package';
    }
}

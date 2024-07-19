<?php

namespace App\Models;

use App\Enums\PluginStatus;
use Exception;
use GuzzleHttp\Client;

/**
 * @property string $package
 * @property string $class
 * @property PluginStatus $status
 * @property string $name
 * @property string|null $description
 * @property string $author
 * @property string $version
 * @property string $panel
 * @property string|null $panel_version
 * @property string $category
 * @property string $update_url
 */
class Plugin extends Model
{
    public const RESOURCE_NAME = 'plugin';

    protected $primaryKey = 'package';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'plugins';

    protected $fillable = [
        'package', 'class', 'name', 'description', 'author', 'version', 'panel', 'panel_version', 'category', 'update_url',
    ];

    public static array $validationRules = [
        'package' => 'required|string',
        'class' => 'required|string',
        'status' => 'string',
        'name' => 'required|string',
        'description' => 'nullable|string',
        'author' => 'required|string',
        'version' => 'required|string',
        'panel' => 'required|string|in:admin,app,both',
        'panel_version' => 'nullable|string',
        'category' => 'required|string|in:plugin,theme,language',
        'update_url' => 'required|string',
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

    public function isCompatible(): bool
    {
        if ($this->panel_version === null) {
            return true;
        }

        if (config('app.version') === 'canary') {
            return false;
        }

        return $this->panel_version === config('app.version');
    }

    public function isUpdateAvailable(): bool
    {
        $updateData = $this->getUpdateData();

        return $updateData && $updateData['version'] === $this->version;
    }

    public function getUpdateData(): array
    {
        return cache()->remember("plugin:{$this->package}:update_data", now()->addMinutes(5), function () {
            try {
                $client = new Client();
                $response = $client->request('GET', $this->update_url,
                    [
                        'timeout' => config('panel.guzzle.timeout'),
                        'connect_timeout' => config('panel.guzzle.connect_timeout'),
                    ]
                );
                if ($response->getStatusCode() === 200) {
                    return json_decode($response->getBody(), true) ?? [];
                }
            } catch (Exception) {
            }

            return [];
        });
    }
}

<?php

namespace App\Http\Requests\Api\Application\Plugins;

use App\Models\Plugin;

class UpdatePluginRequest extends StorePluginRequest
{
    /**
     * Apply validation rules to this request.
     */
    public function rules(array $rules = null): array
    {
        /** @var Plugin $plugin */
        $plugin = $this->route()->parameter('plugin');

        return Plugin::getRulesForUpdate($plugin->package);
    }
}

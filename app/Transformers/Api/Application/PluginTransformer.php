<?php

namespace App\Transformers\Api\Application;

use App\Models\Plugin;

class PluginTransformer extends BaseTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Plugin::RESOURCE_NAME;
    }

    public function transform(Plugin $model)
    {
        return $model->toArray();
    }
}

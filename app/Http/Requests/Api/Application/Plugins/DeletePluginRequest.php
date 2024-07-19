<?php

namespace App\Http\Requests\Api\Application\Plugins;

use App\Services\Acl\Api\AdminAcl;
use App\Http\Requests\Api\Application\ApplicationApiRequest;

class DeletePluginRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_PLUGINS;

    protected int $permission = AdminAcl::WRITE;
}

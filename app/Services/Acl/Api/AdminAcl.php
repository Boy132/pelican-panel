<?php

namespace App\Services\Acl\Api;

use App\Models\ApiKey;

class AdminAcl
{
    /**
     * The different types of permissions available for API keys. This
     * implements a read/write/none permissions scheme for all endpoints.
     */
    public const NONE = 0;
    public const READ = 1;
    public const WRITE = 2;

    // TODO: remove constants
    public const RESOURCE_SERVERS = 'servers';
    public const RESOURCE_NODES = 'nodes';
    public const RESOURCE_ALLOCATIONS = 'allocations';
    public const RESOURCE_USERS = 'users';
    public const RESOURCE_EGGS = 'eggs';
    public const RESOURCE_DATABASE_HOSTS = 'database_hosts';
    public const RESOURCE_SERVER_DATABASES = 'server_databases';

    /**
     * Determine if an API key has permission to perform a specific read/write operation.
     */
    public static function can(int $permission, int $action = self::READ): bool
    {
        if ($permission & $action) {
            return true;
        }

        return false;
    }

    /**
     * Determine if an API Key model has permission to access a given resource
     * at a specific action level.
     */
    public static function check(ApiKey $key, string $resource, int $action = self::READ): bool
    {
        return self::can(data_get($key, 'permissions.' . $resource, self::NONE), $action);
    }

    /**
     * Returns a list of all possible permissions.
     */
    public static function getResourceList(): array
    {
        return ApiKey::getPermissionsList();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Spatie\Permission\Models\Role as BaseRole;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Permission[] $permissions
 * @property int|null $permissions_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property int|null $users_count
 */
class Role extends BaseRole
{
    public const RESOURCE_NAME = 'role';

    public const ROOT_ADMIN = 'Root Admin';

    public const DEFAULT_GUARD_NAME = 'web';

    public const MODEL_SPECIFIC_PERMISSIONS = [
        'egg' => [
            'import',
            'export',
        ],
    ];

    public const SPECIAL_PERMISSIONS = [
        'settings' => [
            'view',
            'update',
        ],
        'health' => [
            'view',
        ],
        'activity' => [
            'seeIps',
        ],
    ];

    public function hasRoleScope(IlluminateModel $model): bool
    {
        return RoleScope::where('role_id', $this->id)->where('scope_type', strtolower(class_basename($model)))->where('scope_id', $model->getKey())->count() >= 1;
    }

    public function isRootAdmin(): bool
    {
        return $this->name === self::ROOT_ADMIN;
    }

    public static function getRootAdmin(): self
    {
        /** @var self $role */
        $role = self::findOrCreate(self::ROOT_ADMIN, self::DEFAULT_GUARD_NAME);

        return $role;
    }
}

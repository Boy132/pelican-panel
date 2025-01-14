<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $role_id
 * @property \App\Models\Role $role
 * @property string $scope_type
 * @property int $scope_id
 */
class RoleScope extends Model
{
    protected $table = 'role_scope';

    protected $primaryKey = null;

    public $incrementing = false;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}

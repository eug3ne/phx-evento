<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view users', 'create users', 'edit users', 'delete users',
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'view permissions',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view items', 'create items', 'edit items', 'delete items',
            'view tags', 'create tags', 'edit tags', 'delete tags',
            'view tickets', 'create tickets', 'edit tickets', 'delete tickets',
            'view attendees', 'create attendees', 'edit attendees', 'delete attendees',
            'view venues', 'create venues', 'edit venues', 'delete venues',
            'view todos', 'create todos', 'edit todos', 'delete todos',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        $allPermissions = Permission::where('guard_name', 'api')->get();

        $roles = [
            'admin', 'developer', 'api', 'moderator', 'creator',
            'organizer', 'planner', 'member', 'guest', 'venue',
            'speaker', 'sponsor', 'volunteer', 'vendor',
        ];

        foreach ($roles as $roleName) {
            $role = Role::findOrCreate($roleName, 'api');
            $role->syncPermissions($allPermissions);
        }
    }
}

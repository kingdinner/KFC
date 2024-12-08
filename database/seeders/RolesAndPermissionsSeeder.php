<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\PermissionRoleDetail;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'api';

        // Create roles
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guardName]);
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guardName]);
        $roleManager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => $guardName]);
        $roleTeamMember = Role::firstOrCreate(['name' => 'team-member', 'guard_name' => $guardName]);

        // Define the permissions and actions for User Management and Labor Management
        $permissions = [
            'User Management' => [
                'Add/Edit User' => ['create', 'update', 'delete'],
                'Delete User' => ['create', 'update', 'delete'],
                'View User' => ['create', 'update', 'delete'],
                'Add/Edit User Roles' => ['create', 'update', 'delete'],
                'View User Roles' => ['create', 'update', 'delete'],
                'Delete User Roles' => ['create', 'update', 'delete'],
                'Module Permission' => ['create', 'update', 'delete'],
                'Notification Permission' => ['create', 'update', 'delete'],
                'Archive Users' => ['create', 'update', 'delete'],
            ],
            'Labor Management' => [
                'Create Labor Management' => ['create', 'update', 'delete']
            ]
        ];

        // Loop through each module and create permissions
        foreach ($permissions as $module => $actions) {
            foreach ($actions as $permissionName => $actionArray) {
                // Create permission in the system
                $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => $guardName]);

                // Attach permission details for admin
                PermissionRoleDetail::create([
                    'permission_id' => $permission->id,
                    'role_id' => $roleAdmin->id,
                    'permission_array' => $actionArray,
                ]);

                // Attach permission details for manager (example, let's give fewer permissions)
                PermissionRoleDetail::create([
                    'permission_id' => $permission->id,
                    'role_id' => $roleManager->id,
                    'permission_array' => ['read'],  // Managers might only have 'read' access
                ]);
            }
        }

        // Fetch all permissions again (including newly created ones)
        $allPermissions = Permission::where('guard_name', $guardName)->get();

        // Assign all permissions to super-admin and admin roles using Spatie's permission system
        $roleSuperAdmin->syncPermissions($allPermissions);
        $roleAdmin->syncPermissions($allPermissions);

        // Manually assign specific permissions to manager and team-member roles
        // These roles will use permission arrays via PermissionRoleDetail for finer control
        $roleManager->syncPermissions([
            Permission::where('name', 'Archive Users')->first(),
        ]);

        $roleTeamMember->syncPermissions([
            Permission::where('name', 'Archive Users')->first(),
        ]);
    }
}

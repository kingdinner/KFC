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
        $roleTeamLeader = Role::firstOrCreate(['name' => 'team-leader', 'guard_name' => $guardName]);

        // Define the permissions and actions for User Management and Labor Management
        $permissions = [
            'User Management' => ['view', 'edit'],
            'Labor Management' =>  ['view', 'edit'],
            'User Account' =>  ['view', 'edit'],
            'User Role' =>  ['view', 'edit'],
            'Authentication' =>  ['view', 'edit'],
            'User Profile' =>  ['view', 'edit'],
            'Labor Forecast' =>  ['view', 'edit'],
            'Dashboard' =>  ['view', 'edit'],
            'Borrow Team Member' =>  ['view', 'edit'],
            'Digital Shift Member' =>  ['view', 'edit'],
            'TMAR' =>  ['view', 'edit'],
            'Approval' =>  ['view', 'edit'],
            'Shift Approval' =>  ['view', 'edit'],
            'Transfer Approval' =>  ['view', 'edit'],
            'Date Management' =>  ['view', 'edit'],
            'Store Management' =>  ['view', 'edit'],
            'Skill Level Management' =>  ['view', 'edit'],
            'Faqs and Policies Management' =>  ['view', 'edit'],
            'Reports' =>  ['view', 'edit'],
        ];

        // Loop through each module and create permissions
        foreach ($permissions as $permissionName => $actions) {
                // Create permission in the system
                $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => $guardName]);
                // Attach permission details for admin
                PermissionRoleDetail::create([
                    'permission_id' => $permission->id,
                    'role_id' => $roleAdmin->id,
                    'permission_array' => $actions,
                ]);

                PermissionRoleDetail::create([
                    'permission_id' => $permission->id,
                    'role_id' => $roleManager->id,
                    'permission_array' => ['read','edit'],  // Managers might only have 'read' access
                ]);

                PermissionRoleDetail::create([
                    'permission_id' => $permission->id,
                    'role_id' => $roleTeamMember->id,
                    'permission_array' => ['read','edit'],  // Managers might only have 'read' access
                ]);

                PermissionRoleDetail::create([
                    'permission_id' => $permission->id,
                    'role_id' => $roleTeamLeader->id,
                    'permission_array' => ['read','edit'],  // Managers might only have 'read' access
                ]);
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
        
        $roleTeamLeader->syncPermissions([
            Permission::where('name', 'Archive Users')->first(),
        ]);
    }
}